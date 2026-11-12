<?php

namespace App\Filament\Resources\Paiements;

use App\Actions\Reservations\RefusePayment;
use App\Actions\Reservations\ValidatePayment;
use App\Enums\PaiementStatus;
use App\Filament\Resources\Paiements\Pages\ManagePaiements;
use App\Models\Paiement;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaiementResource extends Resource
{
    protected static ?string $model = Paiement::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Paiements à vérifier';

    protected static ?string $modelLabel = 'paiement';

    protected static ?string $pluralModelLabel = 'paiements';

    protected static string|\UnitEnum|null $navigationGroup = 'Ventes';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Paiement::where('statut', PaiementStatus::EnVerification->value)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('15s')
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['reservation.user', 'reservation.zone', 'reservation.typeBillet', 'reservation.depart.evenement']))
            ->columns([
                TextColumn::make('reservation.ref')->label('Réservation')->searchable()->copyable(),
                TextColumn::make('reservation.user.name')->label('Client')->searchable(),
                TextColumn::make('montant_declare')
                    ->label('Déclaré')
                    ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', ' ').' FCFA'),
                TextColumn::make('montant_attendu')
                    ->label('Attendu')
                    ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', ' ').' FCFA'),
                TextColumn::make('quantite_deduite')->label('Places déduites'),
                TextColumn::make('controle')->badge(),
                TextColumn::make('statut')->badge(),
                TextColumn::make('transaction_id')->label('Transaction')->searchable()->toggleable(),
                TextColumn::make('created_at')->label('Reçu le')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('statut')->options(PaiementStatus::class)->default(PaiementStatus::EnVerification->value),
            ])
            ->recordActions([
                Action::make('verifier')
                    ->label('Vérifier')
                    ->icon('heroicon-o-eye')
                    ->color('warning')
                    ->visible(fn (Paiement $record) => $record->statut === PaiementStatus::EnVerification)
                    ->modalHeading(fn (Paiement $record) => 'Paiement · '.$record->reservation->ref)
                    ->modalContent(fn (Paiement $record) => view('filament.receipt', ['record' => $record]))
                    ->modalSubmitActionLabel('Valider')
                    ->extraModalFooterActions([
                        Action::make('valider_n')
                            ->label('Valider pour N places')
                            ->color('gray')
                            ->schema([
                                TextInput::make('places')
                                    ->label('Places à valider')
                                    ->numeric()
                                    ->minValue(1)
                                    ->required(),
                            ])
                            ->action(function (array $data, Paiement $record) {
                                app(ValidatePayment::class)->handle($record, auth()->user(), (int) $data['places']);
                                Notification::make()->title('Paiement validé pour '.$data['places'].' place(s)')->success()->send();
                            })
                            ->cancelParentActions(),
                        Action::make('refuser')
                            ->label('Refuser')
                            ->color('danger')
                            ->schema([
                                Textarea::make('motif')
                                    ->label('Motif du refus (visible par le client)')
                                    ->required()
                                    ->maxLength(200),
                            ])
                            ->action(function (array $data, Paiement $record) {
                                app(RefusePayment::class)->handle($record, auth()->user(), $data['motif']);
                                Notification::make()->title('Paiement refusé, le client peut renvoyer un reçu')->warning()->send();
                            })
                            ->cancelParentActions(),
                    ])
                    ->action(function (Paiement $record) {
                        app(ValidatePayment::class)->handle($record, auth()->user());
                        Notification::make()->title('Paiement validé')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePaiements::route('/'),
        ];
    }
}
