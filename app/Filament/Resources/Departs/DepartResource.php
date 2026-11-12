<?php

namespace App\Filament\Resources\Departs;

use App\Filament\Resources\Departs\Pages\ManageDeparts;
use App\Models\Depart;
use App\Models\Vehicule;
use App\Models\Zone;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DepartResource extends Resource
{
    protected static ?string $model = Depart::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-right-start-on-rectangle';

    protected static ?string $navigationLabel = 'Départs';

    protected static ?string $modelLabel = 'départ';

    protected static ?string $pluralModelLabel = 'départs';

    protected static string|\UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('evenement_id')->label('Événement')->relationship('evenement', 'titre')->required()->searchable(),
            Select::make('vehicule_id')->label('Véhicule')->relationship('vehicule', 'nom', fn ($query) => $query->where('actif', true))
                ->live()
                ->afterStateUpdated(function ($state, Set $set) {
                    $vehicule = Vehicule::find($state);
                    if ($vehicule) {
                        $set('places_total', $vehicule->capacite);
                    }
                }),
            DateTimePicker::make('depart_at')->label('Départ')->required(),
            DateTimePicker::make('retour_at')->label('Retour'),
            TextInput::make('places_total')->label('Places totales')->numeric()->minValue(1)->required()
                ->rules([
                    fn (Get $get) => function (string $attribute, $value, $fail) use ($get) {
                        $vehicule = Vehicule::find($get('vehicule_id'));
                        if ($vehicule && (int) $value > $vehicule->capacite) {
                            $fail('Les places totales ne peuvent pas dépasser la capacité du véhicule ('.$vehicule->capacite.').');
                        }
                    },
                ]),
            TextInput::make('max_par_commande')->label('Maximum par commande')->numeric()->minValue(1)->maxValue(8)->default(8)->required(),
            Select::make('statut')->options([
                'ouvert' => 'Ouvert',
                'ferme' => 'Fermé',
                'annule' => 'Annulé',
                'termine' => 'Terminé',
            ])->default('ouvert')->required(),
            Repeater::make('tarifs')
                ->relationship()
                ->label('Zones desservies')
                ->minItems(1)
                ->schema([
                    Select::make('zone_id')->label('Zone')->options(fn () => Zone::query()->where('actif', true)->orderBy('ordre')->pluck('nom', 'id'))
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set) {
                            $zone = Zone::find($state);
                            if ($zone?->prix_defaut) {
                                $set('prix', $zone->prix_defaut);
                            }
                        })
                        ->distinct()
                        ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                    TextInput::make('prix')->numeric()->minValue(0)->suffix('FCFA')->required(),
                    TextInput::make('point_rdv')->label('Point de rendez-vous'),
                    TimePicker::make('heure_ramassage')->label('Heure de ramassage')->seconds(false),
                    Toggle::make('actif')->default(true),
                ])
                ->columns(2)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('depart_at', 'desc')
            ->columns([
                TextColumn::make('evenement.titre')->label('Événement')->searchable(),
                TextColumn::make('vehicule.nom')->label('Véhicule'),
                TextColumn::make('depart_at')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('places_reservees')->label('Réservées'),
                TextColumn::make('places_total')->label('Total'),
                TextColumn::make('statut')->badge(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDeparts::route('/'),
        ];
    }
}
