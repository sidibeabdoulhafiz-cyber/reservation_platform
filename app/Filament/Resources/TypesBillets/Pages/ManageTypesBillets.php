<?php

namespace App\Filament\Resources\TypesBillets\Pages;

use App\Filament\Resources\TypesBillets\TypeBilletResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTypesBillets extends ManageRecords
{
    protected static string $resource = TypeBilletResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Ajouter un type')];
    }
}
