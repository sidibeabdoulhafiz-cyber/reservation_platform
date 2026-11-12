<?php

namespace App\Actions\Reservations;

use App\Enums\PaiementStatus;
use App\Enums\ReservationStatus;
use App\Models\Paiement;
use App\Models\Reservation;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefusePayment
{
    public function handle(Paiement $paiement, User $admin, string $motif): Reservation
    {
        abort_unless($admin->estAdmin(), 403);

        return DB::transaction(function () use ($paiement, $admin, $motif) {
            $lockedPaiement = Paiement::whereKey($paiement->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedPaiement->statut !== PaiementStatus::EnVerification) {
                throw ValidationException::withMessages([
                    'paiement' => 'Ce paiement a déjà été traité.',
                ]);
            }

            $reservation = Reservation::whereKey($lockedPaiement->reservation_id)->lockForUpdate()->firstOrFail();

            $lockedPaiement->update([
                'statut' => PaiementStatus::Refuse,
                'motif_refus' => $motif,
                'verifie_par' => $admin->id,
                'verifie_at' => now(),
            ]);

            $reservation->update([
                'statut' => ReservationStatus::APayer,
                'expire_at' => now()->addMinutes((int) Setting::get('delai_paiement_min', 30)),
            ]);

            return $reservation;
        });
    }
}
