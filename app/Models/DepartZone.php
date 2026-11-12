<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartZone extends Model
{
    protected $table = 'depart_zone';

    protected $fillable = [
        'depart_id', 'zone_id', 'prix', 'point_rdv', 'heure_ramassage', 'actif',
    ];

    protected function casts(): array
    {
        return [
            'prix' => 'integer',
            'actif' => 'boolean',
        ];
    }

    public function depart(): BelongsTo
    {
        return $this->belongsTo(Depart::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }
}
