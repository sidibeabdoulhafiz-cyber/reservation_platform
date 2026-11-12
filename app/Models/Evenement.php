<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evenement extends Model
{
    protected $fillable = [
        'titre', 'slug', 'description', 'lieu', 'ville',
        'debut_at', 'fin_at', 'image_path', 'statut',
    ];

    protected function casts(): array
    {
        return [
            'debut_at' => 'datetime',
            'fin_at' => 'datetime',
        ];
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
}
