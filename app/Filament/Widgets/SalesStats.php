<?php

namespace App\Filament\Widgets;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesStats extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $toVerify = Reservation::where('statut', ReservationStatus::EnVerification->value)->count();

        $month = Reservation::query()
            ->where('statut', ReservationStatus::Payee->value)
            ->where('date_paiement', '>=', now()->startOfMonth())
            ->selectRaw('COALESCE(SUM(montant), 0) as revenue, COALESCE(SUM(places), 0) as places')
            ->first();

        $today = Reservation::whereDate('created_at', today())->count();

        return [
            Stat::make('Reçus à vérifier', $toVerify)
                ->description($toVerify > 0 ? 'En attente de votre validation' : 'Rien en attente')
                ->color($toVerify > 0 ? 'warning' : 'success'),
            Stat::make('Revenus du mois', number_format((int) $month->revenue, 0, ',', ' ') . ' FCFA')
                ->description((int) $month->places . ' places vendues'),
            Stat::make('Réservations aujourd\'hui', $today),
        ];
    }
}
