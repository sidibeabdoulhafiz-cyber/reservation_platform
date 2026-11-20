<?php

namespace App\Filament\Resources\TypesBillets;

use App\Filament\Resources\TypesBillets\Pages\ManageTypesBillets;
use App\Models\TypeBillet;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class TypeBilletResource extends Resource
{
    protected static ?string $model = TypeBillet::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationLabel = 'Types de billets';

    protected static ?string $modelLabel = 'type de billet';

    protected static ?string $pluralModelLabel = 'types de billets';

    protected static string|\UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->components(static::champs());
    }

    /** @return array<int, mixed> */
    public static function champs(bool $avecEvenement = true): array
    {
        return [
            ...($avecEvenement ? [Select::make('evenement_id')->label('Événement')->relationship('evenement', 'titre')->required()->searchable()->preload()] : []),
            TextInput::make('nom')->label('Catégorie')->placeholder('Ex. Grand public, VIP')->required(),
            Textarea::make('description')->rows(2),
            TextInput::make('prix')->numeric()->minValue(0)->suffix('FCFA')->required(),
            TextInput::make('quota')->numeric()->minValue(1)->required(),
            TextInput::make('max_par_commande')->label('Maximum par commande')->numeric()->minValue(1)->maxValue(2)->default(2)->required(),
            DateTimePicker::make('vente_debut_at')->label('Début de vente'),
            DateTimePicker::make('vente_fin_at')->label('Fin de vente'),
            TextInput::make('ordre')->numeric()->default(0),
            Toggle::make('actif')->default(true),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('evenement.titre')->label('Événement'),
                TextColumn::make('nom')->searchable(),
                TextColumn::make('prix')->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', ' ').' FCFA'),
                TextColumn::make('vendus'),
                TextColumn::make('quota'),
                ToggleColumn::make('actif'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTypesBillets::route('/'),
        ];
    }
}
