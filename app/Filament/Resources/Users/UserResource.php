<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Utilisateurs';

    protected static ?string $modelLabel = 'utilisateur';

    protected static ?string $pluralModelLabel = 'utilisateurs';

    protected static string|\UnitEnum|null $navigationGroup = 'Plateforme';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nom')->required()->maxLength(255),
            TextInput::make('email')->label('E-mail')->email()->required()->unique(ignoreRecord: true),
            TextInput::make('tel')->label('Téléphone')->tel(),
            Select::make('role')->label('Rôle')
                ->options([
                    'client' => 'Client',
                    'controleur' => 'Contrôleur',
                    'admin' => 'Administrateur',
                ])
                ->default('client')->required(),
            Toggle::make('actif')->label('Compte actif')->default(true),
            TextInput::make('password')->label('Mot de passe')->password()->revealable()
                ->dehydrated(fn ($state) => filled($state))
                ->required(fn (string $operation) => $operation === 'create')
                ->helperText('Laissez vide pour conserver le mot de passe actuel.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('email')->label('E-mail')->searchable(),
                TextColumn::make('tel')->label('Téléphone')->searchable()->toggleable(),
                TextColumn::make('role')->label('Rôle')->badge()
                    ->color(fn (string $state) => match ($state) {
                        'admin' => 'warning',
                        'controleur' => 'info',
                        default => 'gray',
                    }),
                ToggleColumn::make('actif')->label('Actif')
                    ->disabled(fn (User $record) => $record->is(auth()->user())), // pas d'auto-suspension
            ])
            ->filters([
                SelectFilter::make('role')->label('Rôle')->options([
                    'client' => 'Client',
                    'controleur' => 'Contrôleur',
                    'admin' => 'Administrateur',
                ]),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
