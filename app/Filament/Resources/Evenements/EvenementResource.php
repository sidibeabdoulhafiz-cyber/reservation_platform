<?php

namespace App\Filament\Resources\Evenements;

use App\Filament\Resources\Evenements\Pages\ManageEvenements;
use App\Models\Evenement;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
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
            TextInput::make('titre')->label('Titre')->required()->maxLength(150)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug((string) $state))),
            TextInput::make('slug')->label('Slug')->required()->unique(ignoreRecord: true),
            Textarea::make('description')->label('Description')->rows(4),
            TextInput::make('lieu')->label('Lieu')->required(),
            TextInput::make('ville')->label('Ville')->default('Abidjan')->required(),
            DateTimePicker::make('debut_at')->label('Début')->required(),
            DateTimePicker::make('fin_at')->label('Fin'),
            FileUpload::make('image_path')->label('Image')->image()->disk('public')->directory('evenements'),
            Select::make('statut')->label('Statut')->options([
                'brouillon' => 'Brouillon',
                'publie' => 'Publié',
                'termine' => 'Terminé',
                'annule' => 'Annulé',
            ])->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('debut_at', 'desc')
            ->columns([
                TextColumn::make('titre')->searchable()->sortable(),
                TextColumn::make('lieu'),
                TextColumn::make('debut_at')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('statut')->badge()
                    ->color(fn (string $state) => match ($state) {
                        'publie' => 'success',
                        'brouillon' => 'gray',
                        default => 'danger',
                    }),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageEvenements::route('/'),
        ];
    }
}
