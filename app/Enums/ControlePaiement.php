<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ControlePaiement: string implements HasColor, HasLabel
{
    case Conforme = 'conforme';
    case Insuffisant = 'insuffisant';
    case Excedent = 'excedent';

    public function getLabel(): string
    {
        return match ($this) {
            self::Conforme => 'Conforme',
            self::Insuffisant => 'Insuffisant',
            self::Excedent => 'Excédent',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Conforme => 'success',
            self::Insuffisant => 'warning',
            self::Excedent => 'info',
        };
    }
}
