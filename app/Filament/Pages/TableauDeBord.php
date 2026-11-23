<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Evenements\EvenementResource;
use Filament\Actions\Action;
use Filament\Pages\Dashboard;
use Illuminate\Support\Facades\Auth;

class TableauDeBord extends Dashboard
{
    protected static ?string $title = 'Tableau de bord';

    protected static ?string $navigationLabel = 'Tableau de bord';

    public function getSubheading(): ?string
    {
        $h = (int) now()->format('G');
        $salut = $h < 12 ? 'Bonjour' : ($h < 18 ? 'Bon après-midi' : 'Bonsoir');

        return $salut.' '.str(Auth::user()?->name)->before(' ').' · '.now()->locale('fr')->translatedFormat('l j F Y');
    }

    public function getColumns(): int|array
    {
        return ['default' => 1, 'lg' => 2];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('site')->label('Voir le site')->icon('heroicon-o-globe-alt')->color('gray')
                ->url(route('home'), shouldOpenInNewTab: true),
            Action::make('nouveau')->label('Nouvel événement')->icon('heroicon-o-plus')
                ->url(EvenementResource::getUrl('create')),
        ];
    }
}
