<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Departs\DepartResource;
use App\Models\Depart;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ProchainsDeparts extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Prochains départs (14 jours)';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => Depart::query()
                ->with(['evenement', 'vehicule'])
                ->whereIn('statut', ['ouvert', 'ferme'])
                ->whereBetween('depart_at', [now()->subHours(6), now()->addDays(14)])
                ->orderBy('depart_at'))
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Aucun départ dans les 14 prochains jours')
            ->emptyStateIcon('heroicon-o-truck')
            ->columns([
                TextColumn::make('depart_at')->label('Départ')
                    ->formatStateUsing(fn ($state) => $state->locale('fr')->translatedFormat('D j M · H\hi'))
                    ->description(fn (Depart $r) => $r->depart_at->locale('fr')->diffForHumans()),
                TextColumn::make('evenement.titre')->label('Événement')->wrap()
                    ->description(fn (Depart $r) => $r->evenement?->lieu),
                TextColumn::make('vehicule.nom')->label('Véhicule')->placeholder('À affecter')
                    ->color(fn ($state) => $state ? null : 'danger'),
                TextColumn::make('remplissage')->label('Places')
                    ->state(fn (Depart $r) => $r->places_reservees.' / '.$r->places_total)
                    ->description(fn (Depart $r) => $r->places_total > 0 ? round($r->places_reservees / $r->places_total * 100).' % rempli' : null)
                    ->color(fn (Depart $r) => $r->placesRestantes() === 0 ? 'success' : null),
                TextColumn::make('statut')->badge()
                    ->formatStateUsing(fn (string $state) => ['ouvert' => 'En vente', 'ferme' => 'Fermé'][$state] ?? $state)
                    ->color(fn (string $state) => ['ouvert' => 'success'][$state] ?? 'gray'),
            ])
            ->recordActions([
                Action::make('ouvrir')->label('Gérer')->icon('heroicon-o-arrow-right')->color('gray')
                    ->url(fn () => DepartResource::getUrl()),
            ]);
    }
}
