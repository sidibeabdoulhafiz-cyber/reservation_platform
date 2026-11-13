<?php

namespace App\Filament\Resources\Evenements;

use App\Filament\Resources\Evenements\Pages\ManageEvenements;
use App\Models\Evenement;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
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
            Section::make('Affiche')
                ->description('Image affichée sur la carte et en tête de la page de réservation. Format paysage conseillé (16:9), 4 Mo max.')
                ->schema([
                    FileUpload::make('image_path')
                        ->hiddenLabel()
                        ->image()
                        ->disk('public')
                        ->directory('evenements')
                        ->visibility('public')
                        ->maxSize(4096)
                        ->imageEditor()
                        ->imageEditorAspectRatioOptions(['16:9', '4:3', '1:1'])
                        ->imageAspectRatio('16:9')
                        ->automaticallyCropImagesToAspectRatio()
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
            'index' => ManageEvenements::route('/'),
        ];
    }
}
