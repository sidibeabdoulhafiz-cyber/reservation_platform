<?php

namespace App\Filament\Widgets;

use App\Enums\PaiementStatus;
use App\Enums\ReservationStatus;
use App\Models\Paiement;
use App\Models\Reservation;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesStats extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $toVerify = Paiement::where('statut', PaiementStatus::EnVerification->value)->count();

        // Le chiffre du mois se base sur la date de validation du paiement (verifie_at).
        $month = Reservation::query()
            ->join('paiements', 'paiements.reservation_id', '=', 'reservations.id')
            ->where('reservations.statut', ReservationStatus::Payee->value)
            ->where('paiements.statut', PaiementStatus::Valide->value)
            ->where('paiements.verifie_at', '>=', now()->startOfMonth())
            ->selectRaw('COALESCE(SUM(reservations.montant), 0) as revenue, COALESCE(SUM(reservations.quantite), 0) as places')
            ->first();

        $today = Reservation::whereDate('created_at', today())->count();

        return [
            Stat::make('Reçus à vérifier', $toVerify)
                ->description($toVerify > 0 ? 'En attente de votre validation' : 'Rien en attente')
                ->color($toVerify > 0 ? 'warning' : 'success'),
            Stat::make('Revenus du mois', number_format((int) $month->revenue, 0, ',', ' ').' FCFA')
                ->description((int) $month->places.' places vendues'),
            Stat::make('Réservations aujourd\'hui', $today),
        ];
    }
}
