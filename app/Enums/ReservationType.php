<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ReservationType: string implements HasLabel
{
    case Transport = 'transport';
    case Billet = 'billet';

    public function getLabel(): string
    {
        return match ($this) {
            self::Transport => 'Place en véhicule',
            self::Billet => 'Billet d\'événement',
        };
    }

    public function prefixBillet(): string
    {
        return match ($this) {
            self::Transport => 'TR',
            self::Billet => 'EV',
        };
    }
}
