<?php

namespace App\Filament\Resources\Zones;

use App\Filament\Resources\Zones\Pages\ManageZones;
use App\Models\Zone;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class ZoneResource extends Resource
{
    protected static ?string $model = Zone::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = 'Zones';

    protected static ?string $modelLabel = 'zone';

    protected static ?string $pluralModelLabel = 'zones';

    protected static string|\UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nom')->label('Nom')->required()->maxLength(100)->unique(ignoreRecord: true),
            TextInput::make('prix_defaut')->label('Prix par défaut')->numeric()->minValue(0)->suffix('FCFA')
                ->helperText('Prérempli ensuite sur chaque départ.'),
            TextInput::make('ordre')->numeric()->default(0),
            Toggle::make('actif')->label('Zone active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('ordre')
            ->columns([
                TextColumn::make('nom')->searchable()->sortable(),
                TextColumn::make('prix_defaut')
                    ->label('Prix défaut')
                    ->formatStateUsing(fn ($state) => $state ? number_format((int) $state, 0, ',', ' ').' FCFA' : '—'),
                ToggleColumn::make('actif')->label('Active'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageZones::route('/'),
        ];
    }
}
