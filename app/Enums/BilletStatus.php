<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BilletStatus: string implements HasColor, HasLabel
{
    case Valide = 'valide';
    case Utilise = 'utilise';
    case Annule = 'annule';

    public function getLabel(): string
    {
        return match ($this) {
            self::Valide => 'Valide',
            self::Utilise => 'Déjà utilisé',
            self::Annule => 'Annulé',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Valide => 'success',
            self::Utilise => 'warning',
            self::Annule => 'danger',
        };
    }
}
