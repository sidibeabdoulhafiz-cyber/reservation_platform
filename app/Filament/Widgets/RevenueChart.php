<?php

namespace App\Filament\Widgets;

use App\Enums\PaiementStatus;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Filament\Widgets\ChartWidget;

class RevenueChart extends ChartWidget
{
    protected ?string $heading = 'Ventes validées · 30 derniers jours';

    protected ?string $description = 'Montant des paiements validés par jour, en FCFA.';

    protected static ?int $sort = 4;

    protected ?string $maxHeight = '260px';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $from = now()->subDays(29)->startOfDay();

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
        for ($i = 0; $i < 30; $i++) {
            $day = $from->copy()->addDays($i);
            $labels[] = $day->format('d/m');
            $data[] = (int) ($rows[$day->toDateString()] ?? 0);
        }

        return [
            'datasets' => [[
                'label' => 'Ventes (FCFA)',
                'data' => $data,
                'borderColor' => '#FB7212',
                'backgroundColor' => 'rgba(251, 114, 18, .12)',
                'fill' => true,
                'tension' => .35,
                'pointRadius' => 2,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return ['plugins' => ['legend' => ['display' => false]], 'scales' => ['y' => ['beginAtZero' => true]]];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
