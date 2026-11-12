<?php

namespace App\Actions\Reservations;

use App\Enums\ReservationType;
use App\Models\Depart;
use App\Models\Reservation;
use App\Models\TypeBillet;
use Illuminate\Validation\ValidationException;

class GererPlaces
{
    public function bloquer(Reservation $reservation, ?int $quantite = null): void
    {
        $n = $quantite ?? $reservation->quantite;
        $this->ajuster($reservation, $n);
    }

    public function liberer(Reservation $reservation, ?int $quantite = null): void
    {
        $n = $quantite ?? $reservation->quantite;
        $this->ajuster($reservation, -$n);
    }

    public function ajuster(Reservation $reservation, int $delta): void
    {
        if ($delta === 0) {
            return;
        }

        if ($reservation->type === ReservationType::Transport) {
            $this->ajusterDepart((int) $reservation->depart_id, $delta);

            return;
        }

        $this->ajusterTypeBillet((int) $reservation->type_billet_id, $delta);
    }

    private function ajusterDepart(int $departId, int $delta): void
    {
        $depart = Depart::whereKey($departId)->lockForUpdate()->firstOrFail();

        $nouveau = $depart->places_reservees + $delta;

        if ($nouveau < 0) {
            $nouveau = 0;
        }

        if ($nouveau > $depart->places_total) {
            throw ValidationException::withMessages([
                'quantite' => 'Il ne reste plus assez de places sur ce départ.',
            ]);
        }

        $depart->update(['places_reservees' => $nouveau]);
    }

    private function ajusterTypeBillet(int $typeBilletId, int $delta): void
    {
        $type = TypeBillet::whereKey($typeBilletId)->lockForUpdate()->firstOrFail();

        $nouveau = $type->vendus + $delta;

        if ($nouveau < 0) {
            $nouveau = 0;
        }

        if ($nouveau > $type->quota) {
            throw ValidationException::withMessages([
                'quantite' => 'Il ne reste plus assez de billets dans cette catégorie.',
            ]);
        }

        $type->update(['vendus' => $nouveau]);
    }
}
