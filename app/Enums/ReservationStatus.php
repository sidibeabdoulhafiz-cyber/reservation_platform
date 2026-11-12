<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ReservationStatus: string implements HasColor, HasLabel
{
    case APayer = 'a_payer';
    case EnVerification = 'en_verification';
    case Payee = 'payee';
    case Annulee = 'annulee';
    case Expiree = 'expiree';

    public function getLabel(): string
    {
        return match ($this) {
            self::APayer => 'À payer',
            self::EnVerification => 'En vérification',
            self::Payee => 'Payée',
            self::Annulee => 'Annulée',
            self::Expiree => 'Expirée',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::APayer => 'gray',
            self::EnVerification => 'warning',
            self::Payee => 'success',
            self::Annulee, self::Expiree => 'danger',
        };
    }
}
