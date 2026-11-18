<?php

namespace App\Actions\Reservations;

use App\Enums\BilletStatus;
use App\Models\Billet;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Contrôle d'un billet par un agent.
 *
 * Billet de transport (aller-retour, un billet par passager) : deux contrôles distincts.
 *  - « aller » : montée vers l'événement. Le billet reste valide pour le retour.
 *  - « retour » : montée pour le retour. Le billet est alors entièrement utilisé.
 * Billet d'entrée : un seul contrôle.
 */
class ScanBillet
{
    public const SENS = ['aller', 'retour'];

    /**
     * @return array{statut: string, message: string, billet: ?Billet, sens: string}
     */
    public function handle(string $numero, User $acteur, string $sens = 'aller'): array
    {
        abort_unless($acteur->peutScanner(), 403);

        $numero = strtoupper(trim($numero));
        $sens = in_array($sens, self::SENS, true) ? $sens : 'aller';

        return DB::transaction(function () use ($numero, $acteur, $sens) {
            $billet = Billet::query()
                ->with(['reservation.user', 'reservation.zone', 'depart.evenement', 'typeBillet.evenement'])
                ->where('numero', $numero)
                ->lockForUpdate()
                ->first();

            if (! $billet) {
                return $this->resultat('inconnu', 'Billet inconnu. Vérifiez le numéro.', null, $sens);
            }

            if ($billet->statut === BilletStatus::Annule) {
                return $this->resultat('annule', 'Ce billet a été annulé.', $billet, $sens);
            }

            // Billet d'entrée : contrôle unique.
            if (! $billet->depart_id) {
                if ($billet->statut === BilletStatus::Utilise) {
                    return $this->resultat('utilise', 'Billet déjà utilisé'.$this->le($billet->utilise_at).'.', $billet, 'entree');
                }

                $billet->update(['statut' => BilletStatus::Utilise, 'utilise_at' => now(), 'scanne_par' => $acteur->id]);

                return $this->resultat('valide', 'Billet valide. Entrée autorisée.', $billet->fresh(), 'entree');
            }

            if ($sens === 'aller') {
                if ($billet->aller_scanne_at) {
                    return $this->resultat('utilise', 'Aller déjà contrôlé'.$this->le($billet->aller_scanne_at).'.', $billet, $sens);
                }

                if ($billet->retour_scanne_at || $billet->statut === BilletStatus::Utilise) {
                    return $this->resultat('utilise', 'Ce billet a déjà servi pour le retour.', $billet, $sens);
                }

                $billet->update([
                    'aller_scanne_at' => now(),
                    'aller_scanne_par' => $acteur->id,
                    'utilise_at' => $billet->utilise_at ?? now(),
                    'scanne_par' => $acteur->id,
                ]);

                return $this->resultat('valide', 'Aller validé. Montée autorisée.', $billet->fresh(), $sens);
            }

            // Retour
            if ($billet->retour_scanne_at) {
                return $this->resultat('utilise', 'Retour déjà contrôlé'.$this->le($billet->retour_scanne_at).'.', $billet, $sens);
            }

            $sansAller = $billet->aller_scanne_at === null;

            $billet->update([
                'retour_scanne_at' => now(),
                'retour_scanne_par' => $acteur->id,
                'statut' => BilletStatus::Utilise,
                'utilise_at' => $billet->utilise_at ?? now(),
                'scanne_par' => $acteur->id,
            ]);

            return $this->resultat(
                'valide',
                $sansAller ? 'Retour validé. Montée autorisée (aller non contrôlé).' : 'Retour validé. Montée autorisée.',
                $billet->fresh(),
                $sens,
            );
        });
    }

    private function le($date): string
    {
        return $date ? ' le '.$date->format('d/m à H:i') : '';
    }

    private function resultat(string $statut, string $message, ?Billet $billet, string $sens): array
    {
        return compact('statut', 'message', 'billet', 'sens');
    }
}
