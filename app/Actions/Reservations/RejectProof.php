<?php

namespace App\Actions\Reservations;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class RejectProof
{
    /** Refuse le reçu : la réservation redevient « à payer » et le client peut renvoyer un reçu. */
    public function handle(Reservation $reservation, User $admin, string $motif): Reservation
    {
        abort_unless($admin->role === 'admin', 403);

        if ($reservation->capture_path) {
            Storage::disk('local')->delete($reservation->capture_path);
        }

        $reservation->update([
            'statut' => ReservationStatus::EnAttente,
            'paiement_id' => null,
            'capture_path' => null,
            'motif_refus' => $motif,
        ]);

        return $reservation;
    }
}
