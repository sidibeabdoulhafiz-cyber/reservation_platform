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
        return $schema->components(static::champs());
    }

    /**
     * Champs du départ, réutilisés dans la fiche événement (sans le choix de l'événement).
     *
     * @return array<int, mixed>
     */
    public static function champs(bool $avecEvenement = true): array
    {
        return [
            ...($avecEvenement ? [Select::make('evenement_id')->label('Événement')->relationship('evenement', 'titre')->required()->searchable()->preload()] : []),
            Select::make('vehicule_id')->label('Véhicule')->relationship('vehicule', 'nom', fn ($query) => $query->where('actif', true))
                ->live()
                ->afterStateUpdated(function ($state, Set $set) {
                    $vehicule = Vehicule::find($state);
                    if ($vehicule) {
                        $set('places_total', $vehicule->capacite);
                    }
                }),
            DateTimePicker::make('depart_at')->label('Départ (aller)')->seconds(false)->required()
                ->helperText('Heure de départ du premier arrêt.'),
            DateTimePicker::make('retour_at')->label('Retour')->seconds(false)->after('depart_at')
                ->helperText('Heure de départ du lieu de l’événement.'),
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
                ->label('Itinéraire : arrêts dans l’ordre de passage')
                ->helperText('Glissez les arrêts pour les ordonner. Cochez « Point principal » pour le rassemblement principal.')
                ->orderColumn('ordre')
                ->reorderableWithButtons()
                ->collapsible()
                ->itemLabel(fn (array $state): ?string => ($state['principal'] ?? false ? '★ ' : '')
                    .(Zone::find($state['zone_id'] ?? null)?->nom ?? 'Nouvel arrêt')
                    .(! empty($state['heure_ramassage']) ? ' · '.substr((string) $state['heure_ramassage'], 0, 5) : ''))
                ->addActionLabel('Ajouter un arrêt')
                ->minItems(1)
                ->schema([
                    Select::make('zone_id')->label('Arrêt / quartier')->options(fn () => Zone::query()->where('actif', true)->orderBy('ordre')->orderBy('nom')->pluck('nom', 'id'))
                        ->searchable()
                        ->required()
                        ->createOptionForm([
                            TextInput::make('nom')->label('Nom du quartier / arrêt')->required()->maxLength(80),
                            TextInput::make('prix_defaut')->label('Prix par défaut')->numeric()->minValue(0)->suffix('FCFA'),
                        ])
                        ->createOptionUsing(fn (array $data): int => Zone::create([
                            'nom' => $data['nom'],
                            'prix_defaut' => (int) ($data['prix_defaut'] ?? 0),
                            'ordre' => (int) Zone::max('ordre') + 1,
                            'actif' => true,
                        ])->getKey())
                        ->helperText('Introuvable dans la liste ? Utilisez le bouton + pour créer le quartier.')
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
                    TimePicker::make('heure_ramassage')->label('Heure de passage estimée')->seconds(false),
                    Toggle::make('principal')->label('Point principal'),
                    Toggle::make('actif')->label('Actif')->default(true),
                ])
                ->columns(2)
                ->required(),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('depart_at', 'desc')
            ->columns([
                TextColumn::make('evenement.titre')->label('Événement')->searchable(),
                TextColumn::make('vehicule.nom')->label('Véhicule'),
                TextColumn::make('depart_at')->label('Départ')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('retour_at')->label('Retour')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextColumn::make('places_reservees')->label('Remplissage')
                    ->formatStateUsing(fn ($state, Depart $record) => $record->places_reservees.' / '.$record->places_total)
                    ->color(fn (Depart $record) => $record->placesRestantes() === 0 ? 'danger' : ($record->placesRestantes() <= 3 ? 'warning' : null)),
                TextColumn::make('tarifs_count')->label('Arrêts')->counts('tarifs'),
                TextColumn::make('statut')->badge()
                    ->color(fn (string $state) => match ($state) {
                        'ouvert' => 'success', 'ferme' => 'warning', default => 'gray'
                    }),
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
