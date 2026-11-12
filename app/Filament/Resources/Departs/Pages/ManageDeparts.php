<?php

namespace App\Filament\Resources\Departs\Pages;

use App\Filament\Resources\Departs\DepartResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDeparts extends ManageRecords
{
    protected static string $resource = DepartResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Ajouter un départ')];
    }
}
