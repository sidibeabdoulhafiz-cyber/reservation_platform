<?php

namespace App\Livewire\Reservations;

use App\Enums\ReservationStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.site')]
#[Title('Mes billets')]
class MyReservations extends Component
{
    #[Computed]
    public function reservations(): Collection
    {
        return Auth::user()->reservations()
            ->with(['zone', 'depart.evenement', 'typeBillet.evenement', 'billets'])
            ->latest('id')
            ->limit(50)
            ->get();
    }

    /** @return array{0: Collection, 1: Collection, 2: Collection} [à finaliser, à venir, passées/inactives] */
    #[Computed]
    public function groupes(): array
    {
        $aFinaliser = $this->reservations->filter(fn ($r) => in_array($r->statut, [ReservationStatus::APayer, ReservationStatus::EnVerification], true));

        $payees = $this->reservations->where('statut', ReservationStatus::Payee);
        $aVenir = $payees->filter(fn ($r) => ($r->depart?->evenement ?? $r->typeBillet?->evenement)?->debut_at?->isAfter(now()->subHours(12)));
        $passees = $this->reservations->diff($aFinaliser)->diff($aVenir);

        return [$aFinaliser->values(), $aVenir->values(), $passees->values()];
    }

    public function render()
    {
        return view('livewire.reservations.my-reservations');
    }
}
