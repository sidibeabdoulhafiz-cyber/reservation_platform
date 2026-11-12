<?php

namespace App\Support;

use App\Enums\ControlePaiement;
use InvalidArgumentException;

final class AnalyseMontant
{
    /**
     * @return array{accepte: bool, controle: ?ControlePaiement, places: int, message: ?string}
     */
    public static function analyser(int $montantDeclare, int $prixUnitaire, int $quantiteReservee, int $maxParCommande): array
    {
        if ($prixUnitaire < 1 || $quantiteReservee < 1 || $maxParCommande < 1) {
            throw new InvalidArgumentException('Prix, quantité et maximum doivent être positifs.');
        }

        if ($montantDeclare < 1 || $montantDeclare % $prixUnitaire !== 0) {
            return [
                'accepte' => false,
                'controle' => null,
                'places' => 0,
                'message' => 'Le montant doit être un multiple du prix d\'une place ('.number_format($prixUnitaire, 0, ',', ' ').' FCFA). Vérifiez le montant lu sur le reçu.',
            ];
        }

        $places = intdiv($montantDeclare, $prixUnitaire);

        if ($places > $maxParCommande) {
            return [
                'accepte' => false,
                'controle' => null,
                'places' => $places,
                'message' => 'Ce montant correspond à '.$places.' places, au-dessus du maximum autorisé ('.$maxParCommande.').',
            ];
        }

        $controle = match (true) {
            $places === $quantiteReservee => ControlePaiement::Conforme,
            $places < $quantiteReservee => ControlePaiement::Insuffisant,
            default => ControlePaiement::Excedent,
        };

        return [
            'accepte' => true,
            'controle' => $controle,
            'places' => $places,
            'message' => null,
        ];
    }
}
