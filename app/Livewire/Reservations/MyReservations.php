<?php

namespace App\Livewire\Reservations;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.site')]
#[Title('Mes réservations')]
class MyReservations extends Component
{
    /** Onglet : a-venir, a-finaliser, historique. */
    #[Url(as: 'onglet', except: 'a-venir')]
    public string $onglet = 'a-venir';

    #[Computed]
    public function reservations(): Collection
    {
        return Auth::user()->reservations()
            ->with(['zone', 'depart.evenement', 'depart.tarifs', 'typeBillet.evenement', 'billets'])
            ->latest('id')
            ->limit(100)
            ->get();
    }

    #[Computed]
    public function aFinaliser(): Collection
    {
        return $this->reservations
            ->filter(fn (Reservation $r) => in_array($r->statut, [ReservationStatus::APayer, ReservationStatus::EnVerification], true))
            ->values();
    }

    #[Computed]
    public function aVenir(): Collection
    {
        return $this->reservations
            ->filter(fn (Reservation $r) => $r->statut === ReservationStatus::Payee && $this->dateFin($r)?->isFuture())
            ->sortBy(fn (Reservation $r) => $this->dateDepart($r))
            ->values();
    }

    #[Computed]
    public function historique(): Collection
    {
        return $this->reservations->diff($this->aFinaliser)->diff($this->aVenir)->values();
    }

    /** Le prochain voyage confirmé, mis en avant en haut de page. */
    #[Computed]
    public function prochain(): ?Reservation
    {
        return $this->aVenir->first();
    }

    public function choisir(string $onglet): void
    {
        $this->onglet = in_array($onglet, ['a-venir', 'a-finaliser', 'historique'], true) ? $onglet : 'a-venir';
    }

    public function mount(): void
    {
        // Sans voyage à venir mais avec une réservation à payer : on ouvre directement « À finaliser ».
        if ($this->onglet === 'a-venir' && $this->aVenir->isEmpty() && $this->aFinaliser->isNotEmpty()) {
            $this->onglet = 'a-finaliser';
        }
    }

    private function dateDepart(Reservation $r)
    {
        return $r->depart?->depart_at ?? $r->evenement()?->debut_at;
    }

    private function dateFin(Reservation $r)
    {
        return $r->depart?->retour_at ?? $r->evenement()?->fin_at ?? $r->evenement()?->debut_at?->copy()->addHours(12);
    }

    public function render()
    {
        return view('livewire.reservations.my-reservations');
    }
}
