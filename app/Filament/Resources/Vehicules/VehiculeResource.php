<?php

namespace App\Filament\Resources\Vehicules;

use App\Filament\Resources\Vehicules\Pages\ManageVehicules;
use App\Models\Vehicule;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class VehiculeResource extends Resource
{
    protected static ?string $model = Vehicule::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Véhicules';

    protected static ?string $modelLabel = 'véhicule';

    protected static ?string $pluralModelLabel = 'véhicules';

    protected static string|\UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nom')->required()->maxLength(100),
            TextInput::make('immatriculation')->unique(ignoreRecord: true),
            TextInput::make('capacite')->label('Capacité')->numeric()->minValue(1)->maxValue(80)->required(),
            TextInput::make('chauffeur_nom')->label('Chauffeur'),
            TextInput::make('chauffeur_tel')->label('Téléphone chauffeur')->tel(),
            Toggle::make('actif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nom')->searchable()->sortable(),
                TextColumn::make('immatriculation'),
                TextColumn::make('capacite')->label('Places'),
                TextColumn::make('chauffeur_nom')->label('Chauffeur')->toggleable(),
                ToggleColumn::make('actif'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageVehicules::route('/'),
        ];
    }
}
