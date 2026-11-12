<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaiementStatus: string implements HasColor, HasLabel
{
    case EnVerification = 'en_verification';
    case Valide = 'valide';
    case Refuse = 'refuse';

    public function getLabel(): string
    {
        return match ($this) {
            self::EnVerification => 'En vérification',
            self::Valide => 'Validé',
            self::Refuse => 'Refusé',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::EnVerification => 'warning',
            self::Valide => 'success',
            self::Refuse => 'danger',
        };
    }
}
