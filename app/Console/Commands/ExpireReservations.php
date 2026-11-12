<?php

namespace App\Console\Commands;

use App\Actions\Reservations\GererPlaces;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireReservations extends Command
{
    protected $signature = 'reservations:expirer';

    protected $description = 'Libère les places des réservations à payer dont le délai est dépassé';

    public function handle(GererPlaces $places): int
    {
        $ids = Reservation::query()
            ->where('statut', ReservationStatus::APayer->value)
            ->whereNotNull('expire_at')
            ->where('expire_at', '<=', now())
            ->orderBy('id')
            ->pluck('id');

        $n = 0;

        foreach ($ids as $id) {
            DB::transaction(function () use ($id, $places, &$n) {
                $reservation = Reservation::whereKey($id)->lockForUpdate()->first();

                if (! $reservation) {
                    return;
                }

                if ($reservation->statut !== ReservationStatus::APayer) {
                    return;
                }

                if (! $reservation->expire_at || $reservation->expire_at->isFuture()) {
                    return;
                }

                $places->liberer($reservation);
                $reservation->update([
                    'statut' => ReservationStatus::Expiree,
                    'expire_at' => null,
                ]);
                $n++;
            });
        }

        $this->info($n.' réservation(s) expirée(s).');

        return self::SUCCESS;
    }
}
