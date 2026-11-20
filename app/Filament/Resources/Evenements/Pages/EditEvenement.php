<?php

namespace App\Filament\Resources\Evenements\Pages;

use App\Filament\Resources\Evenements\EvenementResource;
use App\Models\Evenement;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEvenement extends EditRecord
{
    protected static string $resource = EvenementResource::class;

    public function getTitle(): string
    {
        return $this->getRecord()->titre;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('voir')->label('Voir la page publique')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')
                ->url(fn (Evenement $record) => route('evenements.show', $record), shouldOpenInNewTab: true)
                ->visible(fn (Evenement $record) => $record->statut === 'publie'),
            Action::make('publier')->label('Publier')->icon('heroicon-o-check-circle')->color('success')
                ->visible(fn (Evenement $record) => $record->statut === 'brouillon')
                ->requiresConfirmation()
                ->modalDescription('L’événement sera visible sur le site ; les réservations s’ouvrent pour ses départs « ouverts ».')
                ->action(function (Evenement $record) {
                    $record->update(['statut' => 'publie']);
                    $this->refreshFormData(['statut']);
                }),
            DeleteAction::make()
                ->visible(fn (Evenement $record) => ! $record->departs()->whereHas('reservations')->exists()),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Événement enregistré';
    }
}
