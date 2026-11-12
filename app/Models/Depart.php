<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Depart extends Model
{
    protected $fillable = [
        'evenement_id', 'vehicule_id', 'depart_at', 'retour_at',
        'places_total', 'places_reservees', 'max_par_commande', 'statut',
    ];

    protected function casts(): array
    {
        return [
            'depart_at' => 'datetime',
            'retour_at' => 'datetime',
            'places_total' => 'integer',
            'places_reservees' => 'integer',
            'max_par_commande' => 'integer',
        ];
    }

    public function evenement(): BelongsTo
    {
        return $this->belongsTo(Evenement::class);
    }

    public function vehicule(): BelongsTo
    {
        return $this->belongsTo(Vehicule::class);
    }

    public function tarifs(): HasMany
    {
        return $this->hasMany(DepartZone::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function scopeOuverts(Builder $query): Builder
    {
        return $query->where('statut', 'ouvert');
    }

    public function placesRestantes(): int
    {
        return max(0, $this->places_total - $this->places_reservees);
    }
}
