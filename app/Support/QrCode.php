<?php

namespace App\Support;

use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

final class QrCode
{
    public static function svg(string $contenu, int $taille = 180): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($taille, 1),
            new SvgImageBackEnd,
        );

        return (new Writer($renderer))->writeString($contenu);
    }

    /** QR facile à scanner sur un écran : marge blanche de 4 modules, fond blanc forcé. */
    public static function svgEcran(string $contenu, int $taille = 200): string
    {
        $renderer = new ImageRenderer(new RendererStyle($taille, 4), new SvgImageBackEnd);

        return (new Writer($renderer))->writeString($contenu);
    }

    /** PNG (pour les pièces jointes d'e-mail). Null si l'extension GD est absente. */
    public static function png(string $contenu, int $taille = 480): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        return (new Writer(new GDLibRenderer($taille, 2)))->writeString($contenu);
    }
}
