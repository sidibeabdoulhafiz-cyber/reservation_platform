<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\App;

/**
 * Lien de paiement Wave Business personnalisé : le montant exact de la réservation
 * est pré-rempli (paramètre « amount »), le client n'a plus qu'à confirmer dans Wave.
 */
final class Wave
{
    /** Lien marchand par défaut si rien n'est saisi dans Réglages (WAVE_LINK dans .env). */
    public static function lienDeBase(): string
    {
        $lien = trim((string) Setting::get('wave_link', ''));
        if ($lien === '') {
            $lien = trim((string) config('cotatrans.wave_link', ''));
        }

        return self::nettoyer($lien);
    }

    public static function lien(?int $montant = null): ?string
    {
        $lien = self::lienDeBase();

        if ($lien === '' && ! App::environment('production')) {
            $lien = url('/dev/payer');
        }

        if (! self::lienAccepte($lien)) {
            return null;
        }

        if ($montant && $montant > 0 && self::montantActif()) {
            return self::avecMontant($lien, $montant);
        }

        return $lien;
    }

    /** Montant pré-rempli : actif par défaut (le lien Wave Business l'accepte). */
    public static function montantActif(): bool
    {
        return (string) Setting::get('wave_amount', '1') === '1';
    }

    /** Ajoute ou remplace le paramètre amount, sans toucher au reste du lien. */
    public static function avecMontant(string $lien, int $montant): string
    {
        $base = self::nettoyer($lien);
        $separateur = str_contains($base, '?') ? '&' : '?';

        return $base.$separateur.'amount='.$montant;
    }

    /** Retire un éventuel « amount=… » collé avec le lien (ex. lien copié depuis Wave avec ?amount=100). */
    public static function nettoyer(string $lien): string
    {
        $lien = trim($lien);
        if ($lien === '' || ! str_contains($lien, '?')) {
            return $lien;
        }

        [$chemin, $requete] = explode('?', $lien, 2);
        parse_str($requete, $params);
        unset($params['amount']);

        return $params ? $chemin.'?'.http_build_query($params) : $chemin;
    }

    public static function estLienWave(string $lien): bool
    {
        return (string) parse_url($lien, PHP_URL_HOST) === 'pay.wave.com';
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
