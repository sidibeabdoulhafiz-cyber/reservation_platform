<?php

namespace App\Filament\Widgets;

use App\Enums\PaiementStatus;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Filament\Widgets\ChartWidget;

class RevenueChart extends ChartWidget
{
    protected ?string $heading = 'Revenus des 14 derniers jours (FCFA)';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $from = now()->subDays(13)->startOfDay();

        $rows = Reservation::query()
            ->join('paiements', 'paiements.reservation_id', '=', 'reservations.id')
            ->where('reservations.statut', ReservationStatus::Payee->value)
            ->where('paiements.statut', PaiementStatus::Valide->value)
            ->where('paiements.verifie_at', '>=', $from)
            ->selectRaw('DATE(paiements.verifie_at) as d, SUM(reservations.montant) as total')
            ->groupBy('d')
            ->pluck('total', 'd');

        $labels = [];
        $data = [];
        for ($i = 0; $i < 14; $i++) {
            $day = $from->copy()->addDays($i);
            $labels[] = $day->format('d/m');
            $data[] = (int) ($rows[$day->toDateString()] ?? 0);
        }

        return [
            'datasets' => [['label' => 'Revenus', 'data' => $data]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
