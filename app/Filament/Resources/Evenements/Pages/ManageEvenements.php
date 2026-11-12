<?php

namespace App\Filament\Resources\Evenements\Pages;

use App\Filament\Resources\Evenements\EvenementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageEvenements extends ManageRecords
{
    protected static string $resource = EvenementResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Ajouter un événement')];
    }
}
