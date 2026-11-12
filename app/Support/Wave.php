<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\App;

final class Wave
{
    public static function lien(?int $montant = null): ?string
    {
        $lien = (string) Setting::get('wave_link', '');

        if ($lien === '' && ! App::environment('production')) {
            $lien = url('/dev/payer');
        }

        if (! self::lienAccepte($lien)) {
            return null;
        }

        if ($montant && (string) Setting::get('wave_amount', '0') === '1') {
            $separateur = str_contains($lien, '?') ? '&' : '?';

            return $lien.$separateur.'amount='.$montant;
        }

        return $lien;
    }

    public static function lienAccepte(string $lien): bool
    {
        if ($lien === '') {
            return false;
        }

        if (str_starts_with($lien, 'https://')) {
            return true;
        }

        if (App::environment('production')) {
            return false;
        }

        return str_starts_with($lien, 'http://');
    }
}
