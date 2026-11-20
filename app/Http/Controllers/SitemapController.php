<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use Illuminate\Http\Response;

/** Plan du site pour les moteurs de recherche (Google, Bing). */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [
            ['loc' => route('home'), 'changefreq' => 'daily', 'priority' => '1.0', 'lastmod' => now()->toAtomString()],
            ['loc' => route('organisateurs'), 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['loc' => route('contact'), 'changefreq' => 'yearly', 'priority' => '0.4'],
            ['loc' => route('confidentialite'), 'changefreq' => 'yearly', 'priority' => '0.2'],
        ];

        Evenement::publies()
            ->where(fn ($q) => $q->whereNull('fin_at')->where('debut_at', '>=', now()->subDay())->orWhere('fin_at', '>=', now()))
            ->orderBy('debut_at')
            ->get(['slug', 'updated_at', 'image_path', 'titre'])
            ->each(function (Evenement $e) use (&$urls) {
                $urls[] = [
                    'loc' => route('evenements.show', $e),
                    'changefreq' => 'daily',
                    'priority' => '0.9',
                    'lastmod' => $e->updated_at?->toAtomString(),
                    'image' => $e->imageUrl(),
                    'titre' => $e->titre,
                ];
            });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'."\n";
        foreach ($urls as $u) {
            $xml .= '  <url><loc>'.e($u['loc']).'</loc>'
                .(isset($u['lastmod']) ? '<lastmod>'.$u['lastmod'].'</lastmod>' : '')
                .'<changefreq>'.$u['changefreq'].'</changefreq><priority>'.$u['priority'].'</priority>'
                .(! empty($u['image']) ? '<image:image><image:loc>'.e(url($u['image'])).'</image:loc><image:title>'.e($u['titre']).'</image:title></image:image>' : '')
                ."</url>\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
