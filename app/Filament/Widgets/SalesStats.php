<?php

namespace App\Filament\Widgets;

use App\Enums\PaiementStatus;
use App\Enums\ReservationStatus;
use App\Models\Depart;
use App\Models\Reservation;
use Carbon\CarbonInterface;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesStats extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '120s';

    private function ventes(CarbonInterface $de, CarbonInterface $a): object
    {
        // Les ventes se basent sur la date de validation du paiement.
        return Reservation::query()
            ->join('paiements', 'paiements.reservation_id', '=', 'reservations.id')
            ->where('reservations.statut', ReservationStatus::Payee->value)
            ->where('paiements.statut', PaiementStatus::Valide->value)
            ->whereBetween('paiements.verifie_at', [$de, $a])
            ->selectRaw('COALESCE(SUM(reservations.montant), 0) as revenus, COALESCE(SUM(reservations.quantite), 0) as places')
            ->first();
    }

    protected function getStats(): array
    {
        $mois = $this->ventes(now()->startOfMonth(), now());
        $avant = $this->ventes(now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow());
        $evol = (int) $avant->revenus > 0 ? round(((int) $mois->revenus - (int) $avant->revenus) / (int) $avant->revenus * 100) : null;

        $prochains = Depart::where('statut', 'ouvert')->whereBetween('depart_at', [now(), now()->addDays(7)]);
        $total = (int) (clone $prochains)->sum('places_total');
        $prises = (int) (clone $prochains)->sum('places_reservees');
        $taux = $total > 0 ? round($prises / $total * 100) : null;

        $aujourdhui = Reservation::whereDate('created_at', today())
            ->whereNotIn('statut', [ReservationStatus::Annulee->value, ReservationStatus::Expiree->value])->count();

        return [
            Stat::make('Ventes du mois', number_format((int) $mois->revenus, 0, ',', ' ').' FCFA')
                ->description($evol === null ? 'Paiements validés' : ($evol >= 0 ? '+' : '').$evol.' % vs le mois dernier à date')
                ->descriptionIcon($evol !== null && $evol < 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
                ->color($evol !== null && $evol < 0 ? 'danger' : 'success'),
            Stat::make('Places vendues ce mois', number_format((int) $mois->places, 0, ',', ' '))
                ->description('Passagers payés')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),
            Stat::make('Remplissage à 7 jours', $taux === null ? '—' : $taux.' %')
                ->description($total > 0 ? $prises.' / '.$total.' places sur les prochains départs' : 'Aucun départ ouvert cette semaine')
                ->descriptionIcon('heroicon-m-truck')
                ->color($taux === null ? 'gray' : ($taux >= 70 ? 'success' : ($taux >= 40 ? 'warning' : 'danger'))),
            Stat::make('Réservations aujourd’hui', $aujourdhui)
                ->description('Nouvelles demandes du jour')
                ->descriptionIcon('heroicon-m-ticket')
                ->color('primary'),
        ];
    }
}
