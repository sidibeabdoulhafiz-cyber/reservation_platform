<?php

namespace App\Livewire\Reservations;

use App\Actions\Reservations\CancelReservation;
use App\Actions\Reservations\SubmitPaymentProof;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Support\Notifier;
use App\Support\Wave;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.site')]
#[Title('Ma réservation')]
class Suivi extends Component
{
    use AuthorizesRequests, WithFileUploads;

    #[Locked]
    public int $reservationId;

    public string $transaction_id = '';

    public int|string $montant_declare = '';

    public $capture = null;

    public function mount(Reservation $reservation): void
    {
        $this->authorize('view', $reservation);
        $this->reservationId = $reservation->id;
    }

    #[Computed]
    public function reservation(): Reservation
    {
        return Reservation::query()
            ->with([
                'zone',
                'depart.evenement',
                'depart.tarifs',
                'typeBillet.evenement',
                'billets.depart.evenement',
                'billets.typeBillet.evenement',
                'paiements' => fn ($q) => $q->latest('id'),
            ])
            ->findOrFail($this->reservationId);
    }

    #[Computed]
    public function etape(): string
    {
        return match ($this->reservation->statut) {
            ReservationStatus::APayer => 'payer',
            ReservationStatus::EnVerification => 'attendre',
            ReservationStatus::Payee => 'billets',
            default => 'close',
        };
    }

    public function lienWave(): ?string
    {
        return Wave::lien($this->reservation->montant);
    }

    public function submitProof(SubmitPaymentProof $submit): void
    {
        $reservation = $this->reservation;
        $this->authorize('submitProof', $reservation);

        $this->transaction_id = strtoupper(preg_replace('/\s+/', '', $this->transaction_id) ?? '');

        $data = $this->validate([
            'capture' => ['required', 'image', 'max:4096'],
            'transaction_id' => ['required', 'string', 'min:6', 'max:40'],
            'montant_declare' => ['required', 'integer', 'min:1'],
        ], [
            'capture.required' => 'Ajoutez la capture d’écran du reçu Wave.',
            'capture.image' => 'Le fichier doit être une image (capture d’écran).',
            'capture.max' => 'L’image ne doit pas dépasser 4 Mo.',
            'transaction_id.required' => 'Recopie l’identifiant de transaction affiché sur le reçu.',
            'transaction_id.min' => 'L’identifiant de transaction semble trop court.',
            'montant_declare.required' => 'Indiquez le montant payé.',
            'montant_declare.integer' => 'Le montant doit être un nombre entier en FCFA.',
        ]);

        $paiement = $submit->handle($reservation, (int) $data['montant_declare'], $data['transaction_id'], $data['capture']);

        Notifier::paiementRecu($paiement);

        $this->reset('capture', 'transaction_id');
        unset($this->reservation, $this->etape);
    }

    public function annuler(CancelReservation $cancel): void
    {
        $reservation = $this->reservation;
        $this->authorize('submitProof', $reservation);
        $cancel->handle($reservation, auth()->user());
        unset($this->reservation, $this->etape);
    }

    public function render()
    {
        return view('livewire.reservations.suivi');
    }
}
