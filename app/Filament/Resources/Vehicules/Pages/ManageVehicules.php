<?php

namespace App\Filament\Resources\Vehicules\Pages;

use App\Filament\Resources\Vehicules\VehiculeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageVehicules extends ManageRecords
{
    protected static string $resource = VehiculeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Ajouter un véhicule')];
    }
}
