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

    /** Repris côté navigateur pour ajouter/retirer des passagers instantanément (sans aller-retour serveur). */
    public int $prixU = 0;

    public int $maxQte = 1;

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

    /*
     * Sélections par boutons (wire:click) plutôt que par boutons radio masqués :
     * sur iPhone, Safari ne transmettait pas toujours le changement d'arrêt, et le prix restait l'ancien.
     */
    public function choisirDepart(int $id): void
    {
        if ($this->departs->contains('id', $id) && (int) $this->depart_id !== $id) {
            $this->depart_id = $id;
            $this->updatedDepartId();
        }
    }

    public function choisirArret(int $zoneId): void
    {
        if ($this->tarifs->contains('zone_id', $zoneId)) {
            $this->zone_id = $zoneId;
            $this->assurerSelection();
        }
    }

    public function choisirCategorie(int $id): void
    {
        if ($this->typesBillets->contains('id', $id)) {
            $this->type_billet_id = $id;
            $this->assurerSelection();
        }
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
        $this->quantite = max(1, min((int) $this->quantite, max(1, $this->maxPossible)));
        $this->ajusterPassagers();
        // Le nom est la seule preuve qu'un invité voyage : obligatoire pour chaque passager en plus du payeur.
        $regles = ['passagers' => ['array'], 'passagers.0' => ['nullable', 'string', 'max:120']];
        $messages = [];
        foreach (array_keys($this->passagers) as $i) {
            if ($i === 0) {
                continue;
            }
            $regles["passagers.$i"] = ['required', 'string', 'max:120', function (string $attr, mixed $valeur, \Closure $fail) use ($i) {
                if (mb_strlen(preg_replace('/[^\pL]/u', '', (string) $valeur)) < 2) {
                    $fail('Indiquez le nom et le prénom du passager '.($i + 1).'.');
                }
            }];
            $messages["passagers.$i.required"] = 'Indiquez le nom et le prénom du passager '.($i + 1).'.';
            $messages["passagers.$i.max"] = 'Le nom d’un passager ne doit pas dépasser 120 caractères.';
        }
        $this->validate($regles, $messages);

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

            try {
                $this->identifier();
            } catch (ValidationException $e) {
                // Fait vibrer et remonter le bloc d'inscription pour attirer l'attention.
                $this->dispatch('attirer-inscription');
                throw $e;
            }
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
        $evt = $this->evenement;
        $prixMin = $this->departs->flatMap->tarifs->min('prix');
        $date = $evt->debut_at->locale('fr')->translatedFormat('j F Y');

        return view('livewire.reservations.create')
            ->title('Transport '.$evt->titre.' · '.$date)
            ->layoutData([
                'description' => 'Transport aller-retour en car pour '.$evt->titre.' ('.$evt->lieu.', '.$evt->ville.', '.$date.')'
                    .($prixMin ? ', dès '.number_format((int) $prixMin, 0, ',', ' ').' FCFA' : '')
                    .'. Ramassage dans votre quartier, paiement Mobile Money, billet QR. Réservez sur CotaTrans.',
                'ogImage' => $evt->imageUrl() ? url($evt->imageUrl()) : null,
                'ogType' => 'event',
                'canonical' => route('evenements.show', $evt),
                'jsonld' => [$this->jsonLdEvenement($prixMin)],
            ]);
    }

    /** Données structurées schema.org/Event : aide Google à afficher l'événement dans ses résultats. */
    private function jsonLdEvenement(mixed $prixMin): array
    {
        $evt = $this->evenement;

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => $evt->titre,
            'description' => $evt->accroche ?: Str::limit((string) $evt->description, 300),
            'startDate' => $evt->debut_at->toIso8601String(),
            'endDate' => $evt->fin_at?->toIso8601String(),
            'eventStatus' => $evt->statut === 'annule' ? 'https://schema.org/EventCancelled' : 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'image' => $evt->imageUrl() ? [url($evt->imageUrl())] : null,
            'location' => [
                '@type' => 'Place',
                'name' => $evt->lieu,
                'address' => ['@type' => 'PostalAddress', 'addressLocality' => $evt->ville, 'addressCountry' => 'CI'],
            ],
            'organizer' => ['@type' => 'Organization', 'name' => 'CotaTrans', 'url' => url('/')],
            'offers' => $prixMin ? [
                '@type' => 'Offer',
                'name' => 'Transport aller-retour',
                'price' => (int) $prixMin,
                'priceCurrency' => 'XOF',
                'availability' => $this->departs->contains(fn ($d) => $d->placesRestantes() > 0) ? 'https://schema.org/InStock' : 'https://schema.org/SoldOut',
                'url' => route('evenements.show', $evt),
                'validFrom' => $evt->created_at?->toIso8601String(),
            ] : null,
        ]);
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
        $this->prixU = $this->prixUnitaire;
        $this->maxQte = max(1, $this->maxPossible);
    }
}
