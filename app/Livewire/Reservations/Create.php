<?php

namespace App\Livewire\Reservations;

use App\Actions\Reservations\CreateReservation;
use App\Actions\Reservations\SubmitPaymentProof;
use App\Enums\ReservationStatus;
use App\Models\Evenement;
use App\Models\Reservation;
use App\Models\Setting;
use App\Support\Wave;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.site')]
#[Title('Réserver')]
class Create extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public string $canal = 'transport';

    public int|string $evenement_id = 0;

    public int|string $depart_id = 0;

    public int|string $zone_id = 0;

    public int|string $type_billet_id = 0;

    public int $quantite = 1;

    public ?int $reservationId = null;

    public string $transaction_id = '';

    public int|string $montant_declare = '';

    public $capture = null;

    public int $uploadProgress = 0;

    public function mount(): void
    {
        $draft = session('reservation_draft');

        if (is_array($draft)) {
            $this->canal = $draft['type'] ?? 'transport';
            $this->evenement_id = $draft['evenement_id'] ?? 0;
            $this->depart_id = $draft['depart_id'] ?? 0;
            $this->zone_id = $draft['zone_id'] ?? 0;
            $this->type_billet_id = $draft['type_billet_id'] ?? 0;
            $this->quantite = (int) ($draft['quantite'] ?? 1);
        }

        $this->assurerSelection();

        if (Auth::check()) {
            $ouverte = Auth::user()->reservations()
                ->whereIn('statut', [
                    ReservationStatus::APayer->value,
                    ReservationStatus::EnVerification->value,
                ])
                ->latest('id')
                ->first();

            $this->reservationId = $ouverte?->id;

            if (is_array($draft) && Auth::check() && ! $this->reservationId) {
                session()->forget('reservation_draft');
            }
        }
    }

    public function updatedCanal(): void
    {
        $this->quantite = 1;
        $this->assurerSelection();
    }

    public function updatedEvenementId(): void
    {
        $this->depart_id = 0;
        $this->zone_id = 0;
        $this->type_billet_id = 0;
        $this->quantite = 1;
        $this->assurerSelection();
    }

    public function updatedDepartId(): void
    {
        $this->zone_id = 0;
        $this->assurerSelection();
    }

    #[Computed]
    public function evenements(): Collection
    {
        return Evenement::publies()
            ->with([
                'departs' => fn ($q) => $q->ouverts()->orderBy('depart_at')->with([
                    'vehicule',
                    'tarifs' => fn ($t) => $t->where('actif', true)->orderBy('id')->with('zone'),
                ]),
                'typesBillets' => fn ($q) => $q->where('actif', true)->orderBy('ordre')->orderBy('id'),
            ])
            ->orderBy('debut_at')
            ->get()
            ->filter(function (Evenement $e) {
                $transports = $e->departs->filter(fn ($d) => $d->tarifs->isNotEmpty());
                $billets = $e->typesBillets->filter(fn ($t) => $t->enVente());

                return $transports->isNotEmpty() || $billets->isNotEmpty();
            })
            ->values();
    }

    #[Computed]
    public function evenement(): ?Evenement
    {
        return $this->evenements->firstWhere('id', (int) $this->evenement_id);
    }

    #[Computed]
    public function departs(): Collection
    {
        return $this->evenement?->departs->filter(fn ($d) => $d->tarifs->isNotEmpty())->values() ?? collect();
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
        return $this->evenement?->typesBillets->filter(fn ($t) => $t->enVente())->values() ?? collect();
    }

    #[Computed]
    public function typeBillet(): mixed
    {
        return $this->typesBillets->firstWhere('id', (int) $this->type_billet_id);
    }

    #[Computed]
    public function prixUnitaire(): int
    {
        if ($this->canal === 'transport') {
            return (int) ($this->tarif?->prix ?? 0);
        }

        return (int) ($this->typeBillet?->prix ?? 0);
    }

    #[Computed]
    public function maxParCommande(): int
    {
        if ($this->canal === 'transport') {
            return (int) ($this->depart?->max_par_commande ?? 8);
        }

        return (int) ($this->typeBillet?->max_par_commande ?? 2);
    }

    #[Computed]
    public function restant(): int
    {
        if ($this->canal === 'transport') {
            return (int) ($this->depart?->placesRestantes() ?? 0);
        }

        return (int) ($this->typeBillet?->placesRestantes() ?? 0);
    }

    #[Computed]
    public function open(): bool
    {
        return (string) Setting::get('site_ouvert', '1') === '1';
    }

    #[Computed]
    public function reservation(): ?Reservation
    {
        if (! $this->reservationId || ! Auth::check()) {
            return null;
        }

        return Reservation::query()
            ->with([
                'zone',
                'depart.evenement',
                'typeBillet.evenement',
                'billets',
                'paiements' => fn ($q) => $q->latest('id'),
            ])
            ->where('user_id', Auth::id())
            ->find($this->reservationId);
    }

    #[Computed]
    public function etape(): string
    {
        return match ($this->reservation?->statut) {
            ReservationStatus::APayer => 'payer',
            ReservationStatus::EnVerification => 'attendre',
            ReservationStatus::Payee => 'billets',
            default => 'choisir',
        };
    }

    public function increment(): void
    {
        $max = min($this->maxParCommande, max(1, $this->restant));
        if ($this->quantite < $max) {
            $this->quantite++;
        }
    }

    public function decrement(): void
    {
        if ($this->quantite > 1) {
            $this->quantite--;
        }
    }

    public function reserve(CreateReservation $create)
    {
        $choix = [
            'type' => $this->canal,
            'evenement_id' => (int) $this->evenement_id,
            'depart_id' => (int) $this->depart_id,
            'zone_id' => (int) $this->zone_id,
            'type_billet_id' => (int) $this->type_billet_id,
            'quantite' => $this->quantite,
        ];

        if (! Auth::check()) {
            session(['reservation_draft' => $choix]);

            return redirect()->guest(route('login'));
        }

        $reservation = $create->handle(Auth::user(), $choix);
        session()->forget('reservation_draft');

        $this->reservationId = $reservation->id;
        $this->montant_declare = (string) $reservation->montant;
        $this->reset('transaction_id', 'capture');
        $this->resetValidation();
        unset($this->reservation);

        $this->js("document.getElementById('commande')?.scrollIntoView({ block: 'start' })");
    }

    public function nouvelle(): void
    {
        $this->reservationId = null;
        $this->reset('transaction_id', 'capture', 'montant_declare');
        $this->resetValidation();
        unset($this->reservation);
    }

    public function submitProof(SubmitPaymentProof $submit): void
    {
        $reservation = $this->reservation;
        abort_unless($reservation, 404);
        $this->authorize('submitProof', $reservation);

        $this->transaction_id = strtoupper(trim($this->transaction_id));

        $data = $this->validate([
            'montant_declare' => ['required', 'integer', 'min:1'],
            'transaction_id' => ['required', 'string', 'min:6', 'max:40'],
            'capture' => ['required', 'image', 'max:4096'],
        ], [
            'montant_declare.required' => 'Saisissez le montant lu sur le reçu.',
            'montant_declare.integer' => 'Le montant doit être un nombre entier en FCFA.',
            'transaction_id.required' => 'Saisissez l\'identifiant de transaction Wave.',
            'capture.required' => 'Ajoutez la capture d\'écran du reçu.',
            'capture.image' => 'Le fichier doit être une image.',
            'capture.max' => 'L\'image ne doit pas dépasser 4 Mo.',
        ]);

        $submit->handle($reservation, (int) $data['montant_declare'], $data['transaction_id'], $data['capture']);

        $this->reset('capture');
        unset($this->reservation);
    }

    public function lienWave(): ?string
    {
        $montant = $this->reservation?->montant;

        return Wave::lien($montant);
    }

    public function render()
    {
        return view('livewire.reservations.create');
    }

    private function assurerSelection(): void
    {
        if ($this->evenements->isEmpty()) {
            return;
        }

        if (! $this->evenement) {
            $this->evenement_id = $this->evenements->first()->id;
            unset($this->evenement, $this->departs, $this->typesBillets);
        }

        if ($this->canal === 'transport') {
            if ($this->departs->isEmpty() && $this->typesBillets->isNotEmpty()) {
                $this->canal = 'billet';
                unset($this->typesBillets);

                return;
            }

            if (! $this->depart && $this->departs->isNotEmpty()) {
                $this->depart_id = $this->departs->first()->id;
                unset($this->depart, $this->tarifs);
            }

            if (! $this->tarif && $this->tarifs->isNotEmpty()) {
                $this->zone_id = $this->tarifs->first()->zone_id;
            }
        } else {
            if ($this->typesBillets->isEmpty() && $this->departs->isNotEmpty()) {
                $this->canal = 'transport';

                return;
            }

            if (! $this->typeBillet && $this->typesBillets->isNotEmpty()) {
                $this->type_billet_id = $this->typesBillets->first()->id;
            }
        }

        $max = max(1, min($this->maxParCommande, max(1, $this->restant)));
        if ($this->quantite > $max) {
            $this->quantite = $max;
        }
    }
}
