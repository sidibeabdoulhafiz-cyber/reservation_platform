<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zone extends Model
{
    protected $fillable = ['nom', 'prix_defaut', 'ordre', 'actif'];

    protected function casts(): array
    {
        return [
            'prix_defaut' => 'integer',
            'ordre' => 'integer',
            'actif' => 'boolean',
        ];
    }

    public function tarifs(): HasMany
    {
        return $this->hasMany(DepartZone::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
