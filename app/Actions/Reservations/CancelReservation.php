<?php

namespace App\Actions\Reservations;

use App\Enums\BilletStatus;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CancelReservation
{
    /**
     * L'admin peut tout annuler ; le client peut annuler sa propre réservation tant qu'elle est « à payer ».
     */
    public function handle(Reservation $reservation, User $acteur): Reservation
    {
        $estClient = ! $acteur->estAdmin();
        abort_if($estClient && $reservation->user_id !== $acteur->id, 403);

        return DB::transaction(function () use ($reservation, $estClient) {
            $locked = Reservation::whereKey($reservation->getKey())->lockForUpdate()->firstOrFail();

            if ($estClient && $locked->statut !== ReservationStatus::APayer) {
                abort(403, 'Cette réservation ne peut plus être annulée.');
            }

            if ($locked->statut === ReservationStatus::Annulee || $locked->statut === ReservationStatus::Expiree) {
                return $locked;
            }

            if (in_array($locked->statut, [ReservationStatus::APayer, ReservationStatus::EnVerification, ReservationStatus::Payee], true)) {
                app(GererPlaces::class)->liberer($locked);
            }

            $locked->billets()->update([
                'statut' => BilletStatus::Annule,
            ]);

            $locked->update([
                'statut' => ReservationStatus::Annulee,
                'expire_at' => null,
            ]);

            return $locked;
        });
    }
}
