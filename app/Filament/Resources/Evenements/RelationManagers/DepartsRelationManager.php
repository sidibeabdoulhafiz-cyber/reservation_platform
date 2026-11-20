<?php

namespace App\Filament\Resources\Evenements\RelationManagers;

use App\Filament\Resources\Departs\DepartResource;
use App\Models\Depart;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DepartsRelationManager extends RelationManager
{
    protected static string $relationship = 'departs';

    protected static ?string $title = 'Départs et itinéraires';

    protected static ?string $modelLabel = 'départ';

    public function form(Schema $schema): Schema
    {
        return $schema->components(DepartResource::champs(avecEvenement: false))->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('depart_at')
            ->emptyStateHeading('Aucun départ pour le moment')
            ->emptyStateDescription('Ajoutez un départ : véhicule, horaires aller/retour et arrêts dans l’ordre de passage.')
            ->columns([
                TextColumn::make('depart_at')->label('Aller')->dateTime('d/m/Y H:i'),
                TextColumn::make('retour_at')->label('Retour')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextColumn::make('vehicule.nom')->label('Véhicule')->placeholder('—'),
                TextColumn::make('tarifs_count')->label('Arrêts')->counts('tarifs'),
                TextColumn::make('places_reservees')->label('Remplissage')
                    ->formatStateUsing(fn ($state, Depart $record) => $record->places_reservees.' / '.$record->places_total),
                TextColumn::make('statut')->badge()
                    ->color(fn (string $state) => match ($state) {
                        'ouvert' => 'success', 'ferme' => 'warning', default => 'gray'
                    }),
            ])
            ->headerActions([
                CreateAction::make()->label('Ajouter un départ')->modalWidth('5xl'),
            ])
            ->recordActions([
                EditAction::make()->modalWidth('5xl'),
                DeleteAction::make()->visible(fn (Depart $record) => $record->places_reservees === 0),
            ]);
    }
}
