<?php

namespace App\Livewire\Reservations;

use App\Actions\Reservations\CreateReservation;
use App\Enums\UserRole;
use App\Models\Evenement;
use App\Models\Setting;
use App\Models\User;
use App\Support\Notifier;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.site')]
class Create extends Component
{
    public Evenement $evenement;

    public string $canal = 'transport';

    public int|string $depart_id = 0;

    public int|string $zone_id = 0;

    public int|string $type_billet_id = 0;

    public int $quantite = 1;

    /** Noms des passagers (un par place). Le passager 1 est le payeur par défaut. */
    public array $passagers = [''];

    /** Identification du passager non connecté : « inscription » ou « connexion ». */
    public string $mode = 'inscription';

    public string $nom = '';

    public string $tel = '';

    public string $email = '';

    public string $password = '';

    public function mount(Evenement $evenement): void
    {
        abort_unless($evenement->statut === 'publie', 404);
        $this->evenement = $evenement;

        $draft = session('reservation_draft');
        if (is_array($draft) && (int) ($draft['evenement_id'] ?? 0) === $evenement->id) {
            $this->canal = $draft['type'] ?? 'transport';
            $this->depart_id = $draft['depart_id'] ?? 0;
            $this->zone_id = $draft['zone_id'] ?? 0;
            $this->type_billet_id = $draft['type_billet_id'] ?? 0;
            $this->quantite = (int) ($draft['quantite'] ?? 1);
            $this->passagers = array_values((array) ($draft['passagers'] ?? ['']));
        }

        $this->assurerSelection();
    }

    public function updatedCanal(): void
    {
        $this->quantite = 1;
        $this->assurerSelection();
    }

    public function updatedDepartId(): void
    {
        $this->zone_id = 0;
        $this->assurerSelection();
    }

    public function updatedTypeBilletId(): void
    {
        $this->assurerSelection();
    }

    #[Computed]
    public function departs(): Collection
    {
        return $this->evenement->departs()
            ->ouverts()
            ->where('depart_at', '>', now())
            ->orderBy('depart_at')
            ->with(['vehicule', 'tarifs' => fn ($t) => $t->where('actif', true)->with('zone')])
            ->get()
            ->filter(fn ($d) => $d->tarifs->isNotEmpty())
            ->values();
    }

    #[Computed]
    public function depart(): mixed
    {
        return $this->departs->firstWhere('id', (int) $this->depart_id);
    }

    #[Computed]
    public function tarifs(): Collection
    {
        return $this->depart?->tarifs ?? collect();
    }

    #[Computed]
    public function tarif(): mixed
    {
        return $this->tarifs->firstWhere('zone_id', (int) $this->zone_id);
    }

    #[Computed]
    public function typesBillets(): Collection
    {
        return $this->evenement->typesBillets()
            ->where('actif', true)
            ->orderBy('ordre')->orderBy('id')
            ->get()
            ->filter(fn ($t) => $t->enVente())
            ->values();
    }

    #[Computed]
    public function typeBillet(): mixed
    {
        return $this->typesBillets->firstWhere('id', (int) $this->type_billet_id);
    }

    #[Computed]
    public function prixUnitaire(): int
    {
        return $this->canal === 'transport'
            ? (int) ($this->tarif?->prix ?? 0)
            : (int) ($this->typeBillet?->prix ?? 0);
    }

    #[Computed]
    public function maxParCommande(): int
    {
        return $this->canal === 'transport'
            ? (int) ($this->depart?->max_par_commande ?? 8)
            : (int) ($this->typeBillet?->max_par_commande ?? 2);
    }

    #[Computed]
    public function restant(): int
    {
        return $this->canal === 'transport'
            ? (int) ($this->depart?->placesRestantes() ?? 0)
            : (int) ($this->typeBillet?->placesRestantes() ?? 0);
    }

    #[Computed]
    public function maxPossible(): int
    {
        return max(0, min($this->maxParCommande, $this->restant));
    }

    #[Computed]
    public function open(): bool
    {
        return (string) Setting::get('site_ouvert', '1') === '1';
    }

    public function increment(): void
    {
        if ($this->quantite < $this->maxPossible) {
            $this->quantite++;
        }
        $this->ajusterPassagers();
    }

    public function decrement(): void
    {
        if ($this->quantite > 1) {
            $this->quantite--;
        }
        $this->ajusterPassagers();
    }

    /** Retire un passager précis (et donc une place). */
    public function retirerPassager(int $index): void
    {
        if ($this->quantite <= 1 || $index < 1 || $index >= count($this->passagers)) {
            return;
        }
        array_splice($this->passagers, $index, 1);
        $this->quantite--;
        $this->ajusterPassagers();
    }

    /** Une ligne de nom par place. */
    private function ajusterPassagers(): void
    {
        $this->passagers = array_slice(array_pad(array_values($this->passagers), $this->quantite, ''), 0, $this->quantite);
    }

    public function nomPayeur(): string
    {
        return Auth::user()?->name ?: trim($this->nom);
    }

    public function reserve(CreateReservation $create)
    {
        $this->ajusterPassagers();
        $this->validate([
            'passagers' => ['array'],
            'passagers.*' => ['nullable', 'string', 'max:120'],
        ], [
            'passagers.*.max' => 'Le nom d’un passager ne doit pas dépasser 120 caractères.',
        ]);

        $choix = [
            'type' => $this->canal,
            'evenement_id' => $this->evenement->id,
            'depart_id' => (int) $this->depart_id,
            'zone_id' => (int) $this->zone_id,
            'type_billet_id' => (int) $this->type_billet_id,
            'quantite' => $this->quantite,
            'passagers' => $this->passagers,
        ];

        if (! Auth::check()) {
            session(['reservation_draft' => $choix]);
            $this->identifier();
        }

        if (trim((string) ($choix['passagers'][0] ?? '')) === '') {
            $choix['passagers'][0] = Auth::user()->name;
        }

        $reservation = $create->handle(Auth::user(), $choix);
        session()->forget('reservation_draft');

        Notifier::reservationCreee($reservation);

        return $this->redirectRoute('reservations.show', $reservation);
    }

    /** Crée le compte ou connecte le passager sans quitter la page. */
    private function identifier(): void
    {
        if ($this->mode === 'connexion') {
            $this->validate([
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
            ], [
                'email.required' => 'Saisissez votre adresse e-mail.',
                'password.required' => 'Saisissez votre mot de passe.',
            ]);

            $cle = 'login:'.Str::lower($this->email).'|'.request()->ip();
            if (RateLimiter::tooManyAttempts($cle, 5)) {
                throw ValidationException::withMessages([
                    'email' => 'Trop de tentatives. Réessayez dans '.RateLimiter::availableIn($cle).' secondes.',
                ]);
            }

            if (! Auth::attempt(['email' => Str::lower($this->email), 'password' => $this->password, 'actif' => true], true)) {
                RateLimiter::hit($cle);
                throw ValidationException::withMessages([
                    'email' => 'E-mail ou mot de passe incorrect.',
                ]);
            }

            RateLimiter::clear($cle);
            session()->regenerate();
            $this->password = '';

            return;
        }

        $this->tel = preg_replace('/[^\d+]/', '', $this->tel) ?? '';
        $this->email = Str::lower(trim($this->email));

        $this->validate([
            'nom' => ['required', 'string', 'max:120'],
            'tel' => ['required', 'string', 'regex:/^\+?\d{8,15}$/'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::defaults()],
        ], [
            'nom.required' => 'Indiquez votre nom complet.',
            'tel.required' => 'Indiquez votre numéro WhatsApp / mobile.',
            'tel.regex' => 'Numéro invalide. Exemple : 0700000000 ou +2250700000000.',
            'email.required' => 'Indiquez votre e-mail pour recevoir vos billets.',
            'email.email' => 'Adresse e-mail invalide.',
            'email.unique' => 'Un compte existe déjà avec cet e-mail. Choisissez « J’ai déjà un compte ».',
            'password.required' => 'Choisissez un mot de passe.',
        ], [
            'password' => 'mot de passe',
        ]);

        $user = User::create([
            'name' => $this->nom,
            'tel' => $this->tel,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'role' => UserRole::Client->value,
            'actif' => true,
        ]);

        event(new Registered($user));
        Auth::login($user, true);
        session()->regenerate();
        $this->password = '';
    }

    public function render()
    {
        return view('livewire.reservations.create')
            ->title($this->evenement->titre);
    }

    private function assurerSelection(): void
    {
        unset($this->depart, $this->tarifs, $this->tarif, $this->typeBillet);

        if ($this->canal === 'transport' && $this->departs->isEmpty() && $this->typesBillets->isNotEmpty()) {
            $this->canal = 'billet';
        } elseif ($this->canal === 'billet' && $this->typesBillets->isEmpty() && $this->departs->isNotEmpty()) {
            $this->canal = 'transport';
        }

        if ($this->canal === 'transport') {
            if (! $this->depart && $this->departs->isNotEmpty()) {
                $premier = $this->departs->first(fn ($d) => $d->placesRestantes() > 0) ?? $this->departs->first();
                $this->depart_id = $premier->id;
                unset($this->depart, $this->tarifs, $this->tarif);
            }

            if (! $this->tarif && $this->tarifs->isNotEmpty()) {
                $principal = $this->tarifs->firstWhere('principal', true) ?? $this->tarifs->first();
                $this->zone_id = $principal->zone_id;
                unset($this->tarif);
            }
        } elseif (! $this->typeBillet && $this->typesBillets->isNotEmpty()) {
            $this->type_billet_id = $this->typesBillets->first()->id;
            unset($this->typeBillet);
        }

        unset($this->prixUnitaire, $this->maxParCommande, $this->restant, $this->maxPossible);
        $this->quantite = max(1, min($this->quantite, max(1, $this->maxPossible)));
        $this->ajusterPassagers();
    }
}
