<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeBillet extends Model
{
    protected $table = 'types_billets';

    protected $fillable = [
        'evenement_id', 'nom', 'description', 'prix', 'quota', 'vendus',
        'max_par_commande', 'vente_debut_at', 'vente_fin_at', 'actif', 'ordre',
    ];

    protected function casts(): array
    {
        return [
            'prix' => 'integer',
            'quota' => 'integer',
            'vendus' => 'integer',
            'max_par_commande' => 'integer',
            'vente_debut_at' => 'datetime',
            'vente_fin_at' => 'datetime',
            'actif' => 'boolean',
        ];
    }

    public function evenement(): BelongsTo
    {
        return $this->belongsTo(Evenement::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function enVente(): bool
    {
        if (! $this->actif) {
            return false;
        }

        $now = now();

        if ($this->vente_debut_at && $now->lt($this->vente_debut_at)) {
            return false;
        }

        if ($this->vente_fin_at && $now->gt($this->vente_fin_at)) {
            return false;
        }

        return true;
    }

    public function placesRestantes(): int
    {
        return max(0, $this->quota - $this->vendus);
    }
}
