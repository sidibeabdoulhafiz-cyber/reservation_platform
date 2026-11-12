<?php

namespace App\Support;

use App\Models\Billet;
use App\Models\Reservation;

final class Codes
{
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public static function reservation(): string
    {
        do {
            $ref = 'RES-'.self::bloc(6);
        } while (Reservation::where('ref', $ref)->exists());

        return $ref;
    }

    public static function billet(string $prefix): string
    {
        do {
            $numero = $prefix.'-'.self::bloc(4).'-'.self::bloc(4);
        } while (Billet::where('numero', $numero)->exists());

        return $numero;
    }

    private static function bloc(int $longueur): string
    {
        $alphabet = self::ALPHABET;
        $max = strlen($alphabet) - 1;
        $out = '';

        for ($i = 0; $i < $longueur; $i++) {
            $out .= $alphabet[random_int(0, $max)];
        }

        return $out;
    }
}
