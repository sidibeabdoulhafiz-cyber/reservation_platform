<?php

namespace App\Livewire\Reservations;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.site')]
#[Title('Mes réservations')]
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

    public function render()
    {
        return view('livewire.reservations.my-reservations');
    }
}
