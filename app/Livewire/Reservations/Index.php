<?php

namespace App\Livewire\Reservations;

use App\Actions\Reservations\SubmitPaymentProof;
use App\Models\Reservation;
use App\Models\Setting;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('components.layouts.site')]
#[Title('Mes réservations')]
class Index extends Component
{
    use AuthorizesRequests, WithFileUploads, WithPagination;

    public ?int $proofFor = null;

    public string $paiement_id = '';

    public $capture = null;

    #[Computed]
    public function waveLink(): ?string
    {
        return Setting::get('wave_link') ?: null;
    }

    public function openProof(int $id): void
    {
        $reservation = Reservation::findOrFail($id);
        $this->authorize('submitProof', $reservation);

        $this->reset('paiement_id', 'capture');
        $this->resetValidation();
        $this->proofFor = $id;
    }

    public function closeProof(): void
    {
        $this->reset('proofFor', 'paiement_id', 'capture');
        $this->resetValidation();
    }

    public function submitProof(SubmitPaymentProof $submit): void
    {
        $reservation = Reservation::findOrFail($this->proofFor);
        $this->authorize('submitProof', $reservation);

        $this->paiement_id = strtoupper(trim($this->paiement_id));

        $data = $this->validate([
            'paiement_id' => ['required', 'regex:/^T_[A-Z0-9]{6,24}$/'],
            'capture' => ['required', 'image', 'max:4096'],
        ], [
            'paiement_id.required' => 'Saisissez l\'identifiant de transaction Wave.',
            'paiement_id.regex' => 'Format attendu : T_ suivi de 6 à 24 lettres ou chiffres.',
            'capture.required' => 'Ajoutez la capture d\'écran du reçu.',
            'capture.image' => 'Le fichier doit être une image.',
            'capture.max' => 'L\'image ne doit pas dépasser 4 Mo.',
        ]);

        $submit->handle($reservation, $data['paiement_id'], $data['capture']);

        $this->closeProof();
        session()->flash('success', 'Reçu envoyé. Nous vérifions votre paiement.');
    }

    public function render()
    {
        return view('livewire.reservations.index', [
            'reservations' => Auth::user()
                ->reservations()
                ->with('zone')
                ->latest()
                ->paginate(10),
        ]);
    }
}
