<?php

namespace App\Livewire\Reservations;

use App\Actions\Reservations\SubmitPaymentProof;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\Setting;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class MyReservations extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public ?int $proofFor = null;

    public ?int $highlight = null;

    public string $paiement_id = '';

    public $capture = null;

    public ?string $notice = null;

    public function mount(): void
    {
        // Le formulaire de reçu s'ouvre directement sur la dernière réservation à payer.
        $this->proofFor = Auth::user()->reservations()
            ->where('statut', ReservationStatus::EnAttente->value)
            ->latest('id')
            ->value('id');
    }

    #[Computed]
    public function waveLink(): ?string
    {
        return Setting::get('wave_link') ?: null;
    }

    /** Déclenché par la page de réservation dès qu'une réservation est créée. */
    #[On('reservation-created')]
    public function focusNew(int $id): void
    {
        $this->reset('paiement_id', 'capture');
        $this->resetValidation();
        $this->notice = null;
        $this->proofFor = $id;
        $this->highlight = $id;
    }

    public function openProof(int $id): void
    {
        $reservation = Reservation::findOrFail($id);
        $this->authorize('submitProof', $reservation);

        $this->reset('paiement_id', 'capture');
        $this->resetValidation();
        $this->notice = null;
        $this->proofFor = $id;
    }

    public function submitProof(SubmitPaymentProof $submit): void
    {
        $reservation = Reservation::findOrFail($this->proofFor);
        $this->authorize('submitProof', $reservation);

        $this->paiement_id = strtoupper(trim($this->paiement_id));

        $data = $this->validate([
            'capture' => ['required', 'image', 'max:4096'],
            'paiement_id' => ['required', 'regex:/^T_[A-Z0-9]{6,24}$/'],
        ], [
            'capture.required' => 'Ajoutez la capture d\'écran du reçu.',
            'capture.image' => 'Le fichier doit être une image.',
            'capture.max' => 'L\'image ne doit pas dépasser 4 Mo.',
            'paiement_id.required' => 'Saisissez l\'identifiant de transaction Wave.',
            'paiement_id.regex' => 'Format attendu : T_ suivi de 6 à 24 lettres ou chiffres.',
        ]);

        $submit->handle($reservation, $data['paiement_id'], $data['capture']);

        $this->reset('paiement_id', 'capture', 'proofFor');
        $this->highlight = $reservation->id;
        $this->notice = "Reçu envoyé pour {$reservation->ref}. Nous vérifions votre paiement.";
    }

    public function render()
    {
        return view('livewire.reservations.my-reservations', [
            'reservations' => Auth::user()
                ->reservations()
                ->with('zone')
                ->latest('id')
                ->limit(10)
                ->get(),
        ]);
    }
}
