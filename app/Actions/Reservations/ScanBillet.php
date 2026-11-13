<?php

namespace App\Actions\Reservations;

use App\Enums\BilletStatus;
use App\Models\Billet;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ScanBillet
{
    /**
     * @return array{statut: string, message: string, billet: ?Billet}
     */
    public function handle(string $numero, User $acteur): array
    {
        abort_unless($acteur->peutScanner(), 403);

        $numero = strtoupper(trim($numero));

        return DB::transaction(function () use ($numero, $acteur) {
            $billet = Billet::query()
                ->with(['reservation.user', 'depart.evenement', 'typeBillet.evenement'])
                ->where('numero', $numero)
                ->lockForUpdate()
                ->first();

            if (! $billet) {
                return [
                    'statut' => 'inconnu',
                    'message' => 'Billet inconnu.',
                    'billet' => null,
                ];
            }

            if ($billet->statut === BilletStatus::Annule) {
                return [
                    'statut' => 'annule',
                    'message' => 'Ce billet a été annulé.',
                    'billet' => $billet,
                ];
            }

            if ($billet->statut === BilletStatus::Utilise) {
                return [
                    'statut' => 'utilise',
                    'message' => 'Ce billet a déjà été utilisé'
                        .($billet->utilise_at ? ' le '.$billet->utilise_at->format('d/m/Y à H:i') : '')
                        .'.',
                    'billet' => $billet,
                ];
            }

            $billet->update([
                'statut' => BilletStatus::Utilise,
                'utilise_at' => now(),
                'scanne_par' => $acteur->id,
            ]);

            return [
                'statut' => 'valide',
                'message' => 'Billet valide. Entrée autorisée.',
                'billet' => $billet->fresh(),
            ];
        });
    }
}
