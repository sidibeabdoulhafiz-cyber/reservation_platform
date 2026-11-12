<?php

namespace App\Support;

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
}
