<?php

namespace App\Filament\Resources\Evenements;

use App\Filament\Resources\Evenements\Pages\CreateEvenement;
use App\Filament\Resources\Evenements\Pages\EditEvenement;
use App\Filament\Resources\Evenements\Pages\ListEvenements;
use App\Filament\Resources\Evenements\RelationManagers\DepartsRelationManager;
use App\Filament\Resources\Evenements\RelationManagers\TypesBilletsRelationManager;
use App\Models\Evenement;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class EvenementResource extends Resource
{
    protected static ?string $model = Evenement::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Événements';

    protected static ?string $modelLabel = 'événement';

    protected static ?string $pluralModelLabel = 'événements';

    protected static string|\UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Configuration de l’événement')
                ->description('Ce qu’il reste à faire pour ouvrir les réservations.')
                ->schema([
                    Placeholder::make('etat')->hiddenLabel()
                        ->content(fn (?Evenement $record) => static::checklist($record)),
                ])
                ->visibleOn('edit')
                ->collapsible()
                ->columnSpanFull(),
            Section::make('Affiche')
                ->description('Image affichée sur la carte et en tête de la page de réservation. Recadrée automatiquement au format paysage 16:9. JPG, PNG ou WebP, 4 Mo max.')
                ->schema([
                    FileUpload::make('image_path')
                        ->hiddenLabel()
                        ->image()
                        ->disk('public')
                        ->directory('evenements')
                        ->visibility('public')
                        ->maxSize(4096)
                        // Recadrage 16:9 et redimensionnement automatiques, sans fenêtre d'édition.
                        ->imageCropAspectRatio('16:9')
                        ->imageResizeMode('cover')
                        ->imageResizeTargetWidth('1600')
                        ->imageResizeTargetHeight('900')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),
                ])
                ->columnSpanFull(),
            Section::make('Informations')
                ->columns(2)
                ->schema([
                    TextInput::make('titre')->label('Titre')->required()->maxLength(150)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Set $set, Get $get, ?string $state, string $operation) {
                            if ($operation === 'create' || blank($get('slug'))) {
                                $set('slug', Str::slug((string) $state));
                            }
                        }),
                    TextInput::make('slug')->label('Adresse de la page')->prefix('/evenements/')
                        ->required()->unique(ignoreRecord: true)
                        ->helperText('Lien à partager par le promoteur.'),
                    Select::make('categorie')->label('Catégorie')->options(Evenement::CATEGORIES)
                        ->placeholder('Choisir…')
                        ->helperText('Sert au filtre « Concerts, Festivals, Matchs, Sorties » sur le site.'),
                    TextInput::make('accroche')->label('Accroche')->maxLength(160)
                        ->placeholder('Ex. La plus grande soirée rétro de l’année')
                        ->columnSpanFull(),
                    Textarea::make('description')->label('Description')->rows(4)->columnSpanFull(),
                    TextInput::make('lieu')->label('Lieu')->required(),
                    TextInput::make('ville')->label('Ville')->default('Abidjan')->required(),
                    DateTimePicker::make('debut_at')->label('Début')->seconds(false)->required(),
                    DateTimePicker::make('fin_at')->label('Fin')->seconds(false)->after('debut_at'),
                ])
                ->columnSpanFull(),
            Section::make('Publication & communauté')
                ->columns(2)
                ->schema([
                    Select::make('statut')->label('Statut')->options([
                        'brouillon' => 'Brouillon',
                        'publie' => 'Publié',
                        'termine' => 'Terminé',
                        'annule' => 'Annulé',
                    ])->default('brouillon')->required(),
                    TextInput::make('lien_whatsapp')->label('Lien du groupe WhatsApp')
                        ->url()->prefixIcon('heroicon-o-chat-bubble-left-right')
                        ->placeholder('https://chat.whatsapp.com/…')
                        ->helperText('Proposé (facultatif) au passager avec son billet.'),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('debut_at', 'desc')
            ->columns([
                ImageColumn::make('image_path')->label('')->disk('public')->imageHeight(48)->imageWidth(84)
                    ->extraImgAttributes(['style' => 'object-fit:cover;border-radius:8px']),
                TextColumn::make('titre')->searchable()->sortable()
                    ->description(fn (Evenement $record) => $record->lieu),
                TextColumn::make('debut_at')->label('Date')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('categorie')->label('Catégorie')->badge()->color('gray')
                    ->formatStateUsing(fn (?string $state) => Evenement::CATEGORIES[$state] ?? '—')->toggleable(),
                TextColumn::make('departs_count')->label('Départs')->counts('departs'),
                TextColumn::make('statut')->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'publie' => 'Publié',
                        'brouillon' => 'Brouillon',
                        'termine' => 'Terminé',
                        'annule' => 'Annulé',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'publie' => 'success',
                        'brouillon' => 'gray',
                        default => 'danger',
                    }),
            ])
            ->recordActions([
                Action::make('voir')->label('Voir la page')->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Evenement $record) => route('evenements.show', $record), shouldOpenInNewTab: true)
                    ->visible(fn (Evenement $record) => $record->statut === 'publie'),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvenements::route('/'),
            'create' => CreateEvenement::route('/create'),
            'edit' => EditEvenement::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            DepartsRelationManager::class,
            TypesBilletsRelationManager::class,
        ];
    }

    /** Liste de contrôle affichée en haut de la fiche : ce qu'il reste à faire pour vendre. */
    public static function checklist(?Evenement $evt): HtmlString
    {
        if (! $evt) {
            return new HtmlString('');
        }

        $departs = $evt->departs()->withCount(['tarifs' => fn ($q) => $q->where('actif', true)])->get();
        $etapes = [
            ['Affiche ajoutée', (bool) $evt->image_path, 'Recommandé pour la carte et la page.'],
            ['Au moins un départ', $departs->isNotEmpty(), 'Section « Départs et itinéraires » plus bas.'],
            ['Un arrêt actif ou plus sur chaque départ', $departs->isNotEmpty() && $departs->every(fn ($d) => $d->tarifs_count > 0), 'Ajoutez les arrêts dans le départ.'],
            ['Un départ ouvert à la vente', $departs->contains(fn ($d) => $d->statut === 'ouvert' && $d->depart_at?->isFuture()), 'Statut « Ouvert » et date future.'],
            ['Événement publié', $evt->statut === 'publie', 'Bouton « Publier » en haut à droite.'],
        ];

        $html = '<ul style="display:grid;gap:6px;margin:0;padding:0;list-style:none">';
        foreach ($etapes as [$libelle, $ok, $aide]) {
            $html .= '<li style="display:flex;gap:8px;align-items:flex-start">'
                .'<span style="flex:none;width:20px;height:20px;border-radius:50%;display:inline-grid;place-items:center;font-size:12px;color:#fff;background:'.($ok ? '#12805c' : '#c9d1dc').'">'.($ok ? '✓' : '').'</span>'
                .'<span><strong>'.e($libelle).'</strong>'.($ok ? '' : ' <span style="color:#6b7280">· '.e($aide).'</span>').'</span></li>';
        }

        return new HtmlString($html.'</ul>');
    }
}
