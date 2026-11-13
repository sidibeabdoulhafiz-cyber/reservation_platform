<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Evenement extends Model
{
    protected $fillable = [
        'titre', 'accroche', 'slug', 'description', 'lieu', 'ville',
        'debut_at', 'fin_at', 'image_path', 'lien_whatsapp', 'statut',
    ];

    protected function casts(): array
    {
        return [
            'debut_at' => 'datetime',
            'fin_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublies(Builder $query): Builder
    {
        return $query->where('statut', 'publie');
    }

    public function departs(): HasMany
    {
        return $this->hasMany(Depart::class);
    }

    public function typesBillets(): HasMany
    {
        return $this->hasMany(TypeBillet::class);
    }

    /** URL publique de l'affiche, ou null si aucune image. */
    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
            return $this->image_path;
        }

        return Storage::disk('public')->url($this->image_path);
    }

    /** Dégradé de secours, stable par événement, quand il n'y a pas d'affiche. */
    public function couleurSecours(): string
    {
        $palettes = [
            ['#0b3d2e', '#e8590c'],
            ['#1d2b64', '#f8b500'],
            ['#3a0d3f', '#ff6f61'],
            ['#06402b', '#2ec4b6'],
            ['#2d1b0e', '#f77f00'],
        ];
        [$a, $b] = $palettes[$this->id % count($palettes)];

        return "linear-gradient(135deg, {$a} 0%, {$b} 100%)";
    }

    /** Prix le plus bas disponible (transport ou billet), pour « à partir de ». */
    public function prixMin(): ?int
    {
        $transport = $this->relationLoaded('departs')
            ? $this->departs->flatMap->tarifs->where('actif', true)->min('prix')
            : null;

        $billet = $this->relationLoaded('typesBillets')
            ? $this->typesBillets->filter->enVente()->min('prix')
            : null;

        $min = collect([$transport, $billet])->filter(fn ($v) => $v !== null)->min();

        return $min === null ? null : (int) $min;
    }

    public function dateCourte(): string
    {
        return $this->debut_at->locale('fr')->translatedFormat('D j M · H\hi');
    }
}
