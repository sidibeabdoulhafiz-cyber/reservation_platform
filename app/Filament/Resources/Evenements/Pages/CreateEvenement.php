<?php

namespace App\Filament\Resources\Evenements\Pages;

use App\Filament\Resources\Evenements\EvenementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEvenement extends CreateRecord
{
    protected static string $resource = EvenementResource::class;

    protected static ?string $title = 'Nouvel événement';

    protected static bool $canCreateAnother = false;

    /** Après création, on ouvre la fiche pour ajouter départs, arrêts et billets. */
    protected function getRedirectUrl(): string
    {
        return EvenementResource::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Événement créé. Étape suivante : ajoutez un départ et son itinéraire (section « Départs et itinéraires »).';
    }
}
