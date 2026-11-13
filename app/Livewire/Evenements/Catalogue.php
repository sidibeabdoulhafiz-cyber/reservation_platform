<?php

namespace App\Livewire\Evenements;

use App\Enums\ReservationStatus;
use App\Models\Evenement;
use App\Models\Reservation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.site')]
#[Title('Événements')]
class Catalogue extends Component
{
    #[Computed]
    public function evenements(): Collection
    {
        return Evenement::publies()
            ->where(fn ($q) => $q->whereNull('fin_at')->where('debut_at', '>=', now()->subHours(6))
                ->orWhere('fin_at', '>=', now()))
            ->with([
                'departs' => fn ($q) => $q->ouverts()->with(['tarifs' => fn ($t) => $t->where('actif', true)]),
                'typesBillets' => fn ($q) => $q->where('actif', true),
            ])
            ->orderBy('debut_at')
            ->get();
    }

    /** Réservation en attente de paiement / vérification, pour proposer de la reprendre. */
    #[Computed]
    public function enCours(): ?Reservation
    {
        if (! Auth::check()) {
            return null;
        }

        return Auth::user()->reservations()
            ->with(['depart.evenement', 'typeBillet.evenement', 'zone'])
            ->whereIn('statut', [ReservationStatus::APayer->value, ReservationStatus::EnVerification->value])
            ->latest('id')
            ->first();
    }

    public function render()
    {
        return view('livewire.evenements.catalogue');
    }
}
