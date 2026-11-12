<?php

namespace App\Actions\Reservations;

use App\Enums\BilletStatus;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CancelReservation
{
    public function handle(Reservation $reservation, User $admin): Reservation
    {
        abort_unless($admin->estAdmin(), 403);

        return DB::transaction(function () use ($reservation) {
            $locked = Reservation::whereKey($reservation->getKey())->lockForUpdate()->firstOrFail();

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
