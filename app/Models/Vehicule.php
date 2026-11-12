<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicule extends Model
{
    protected $fillable = [
        'nom', 'immatriculation', 'capacite', 'chauffeur_nom', 'chauffeur_tel', 'actif',
    ];

    protected function casts(): array
    {
        return [
            'capacite' => 'integer',
            'actif' => 'boolean',
        ];
    }

    public function departs(): HasMany
    {
        return $this->hasMany(Depart::class);
    }
}
