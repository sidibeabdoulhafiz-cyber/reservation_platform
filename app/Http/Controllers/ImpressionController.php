<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ImpressionController extends Controller
{
    /** Page imprimable des billets : « Imprimer » ou « Enregistrer en PDF » depuis le navigateur. */
    public function __invoke(Reservation $reservation): View
    {
        Gate::authorize('view', $reservation);
        abort_unless($reservation->statut === ReservationStatus::Payee, 404);

        $reservation->load(['zone', 'billets.depart.evenement', 'billets.depart.tarifs', 'billets.typeBillet.evenement', 'depart.evenement', 'typeBillet.evenement']);

        return view('reservations.imprimer', ['reservation' => $reservation]);
    }
}
