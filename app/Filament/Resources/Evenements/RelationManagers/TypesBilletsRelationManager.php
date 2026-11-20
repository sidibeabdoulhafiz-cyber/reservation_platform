<?php

namespace App\Filament\Resources\Evenements\RelationManagers;

use App\Filament\Resources\TypesBillets\TypeBilletResource;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class TypesBilletsRelationManager extends RelationManager
{
    protected static string $relationship = 'typesBillets';

    protected static ?string $title = 'Billets d’entrée (facultatif)';

    protected static ?string $modelLabel = 'catégorie de billet';

    public function form(Schema $schema): Schema
    {
        return $schema->components(TypeBilletResource::champs(avecEvenement: false))->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('Aucun billet d’entrée')
            ->emptyStateDescription('Facultatif : seulement si CotaTrans vend aussi l’entrée de l’événement.')
            ->columns([
                TextColumn::make('nom')->label('Catégorie'),
                TextColumn::make('prix')->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', ' ').' FCFA'),
                TextColumn::make('vendus')->label('Vendus'),
                TextColumn::make('quota'),
                ToggleColumn::make('actif'),
            ])
            ->headerActions([CreateAction::make()->label('Ajouter une catégorie')])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->visible(fn ($record) => (int) $record->vendus === 0),
            ]);
    }
}
