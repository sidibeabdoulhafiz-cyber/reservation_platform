<?php

namespace App\Filament\Widgets;

use App\Enums\PaiementStatus;
use App\Filament\Resources\Departs\DepartResource;
use App\Filament\Resources\Evenements\EvenementResource;
use App\Filament\Resources\Messages\MessageResource;
use App\Filament\Resources\Paiements\PaiementResource;
use App\Models\Depart;
use App\Models\Evenement;
use App\Models\Message;
use App\Models\Paiement;
use Filament\Widgets\Widget;

/** « À faire maintenant » : ce qui attend une action de l'équipe, en un coup d'œil. */
class AFaire extends Widget
{
    protected string $view = 'filament.widgets.a-faire';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '60s';

    protected function getViewData(): array
    {
        $taches = [
            [
                'n' => Paiement::where('statut', PaiementStatus::EnVerification->value)->count(),
                'titre' => 'Reçus à vérifier',
                'aide' => 'Valide ou refuse les paiements',
                'url' => PaiementResource::getUrl(),
                'couleur' => '#FB7212',
                'icone' => 'heroicon-o-banknotes',
            ],
            [
                'n' => Message::where('statut', 'nouveau')->count(),
                'titre' => 'Nouveaux messages',
                'aide' => 'Demandes d’organisateurs et contacts',
                'url' => MessageResource::getUrl(),
                'couleur' => '#0C4173',
                'icone' => 'heroicon-o-inbox',
            ],
            [
                'n' => Evenement::where('statut', 'brouillon')->where('debut_at', '>=', now())->count(),
                'titre' => 'Brouillons à finaliser',
                'aide' => 'Affiche, départs, tarifs puis publier',
                'url' => EvenementResource::getUrl(),
                'couleur' => '#64748b',
                'icone' => 'heroicon-o-pencil-square',
            ],
            [
                'n' => Depart::where('statut', 'ouvert')->whereBetween('depart_at', [now(), now()->addHours(48)])->whereNull('vehicule_id')->count(),
                'titre' => 'Départs sans véhicule',
                'aide' => 'Dans les 48 prochaines heures',
                'url' => DepartResource::getUrl(),
                'couleur' => '#dc2626',
                'icone' => 'heroicon-o-truck',
            ],
        ];

        return [
            'taches' => $taches,
            'toutBon' => collect($taches)->sum('n') === 0,
        ];
    }
}
