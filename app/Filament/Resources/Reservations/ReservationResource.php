<?php

namespace App\Filament\Resources\Reservations;

use App\Actions\Reservations\CancelReservation;
use App\Enums\ReservationStatus;
use App\Filament\Resources\Reservations\Pages\ManageReservations;
use App\Models\Reservation;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReservationResource extends Resource
{
    protected static ?string $model = Reservation::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'Réservations';

    protected static ?string $modelLabel = 'réservation';

    protected static ?string $pluralModelLabel = 'réservations';

    protected static string|\UnitEnum|null $navigationGroup = 'Ventes';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user', 'zone', 'depart.evenement', 'typeBillet']))
            ->columns([
                TextColumn::make('ref')->label('Référence')->searchable()->copyable(),
                TextColumn::make('user.name')->label('Client')->searchable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('quantite')->label('Places'),
                TextColumn::make('montant')
                    ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', ' ').' FCFA')
                    ->sortable(),
                TextColumn::make('statut')->badge(),
                TextColumn::make('created_at')->label('Créée le')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('statut')->options(ReservationStatus::class),
            ])
            ->recordActions([
                Action::make('annuler')
                    ->label('Annuler')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Reservation $record) => $record->statut !== ReservationStatus::Annulee)
                    ->action(function (Reservation $record) {
                        app(CancelReservation::class)->handle($record, auth()->user());
                        Notification::make()->title('Réservation annulée')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageReservations::route('/'),
        ];
    }
}
