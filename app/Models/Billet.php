<?php

namespace App\Models;

use App\Enums\BilletStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Billet extends Model
{
    protected $fillable = [
        'reservation_id', 'numero', 'depart_id', 'type_billet_id',
        'siege', 'nom_passager', 'statut', 'utilise_at', 'scanne_par',
        'aller_scanne_at', 'aller_scanne_par', 'retour_scanne_at', 'retour_scanne_par',
    ];

    protected function casts(): array
    {
        return [
            'statut' => BilletStatus::class,
            'siege' => 'integer',
            'utilise_at' => 'datetime',
            'aller_scanne_at' => 'datetime',
            'retour_scanne_at' => 'datetime',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function depart(): BelongsTo
    {
        return $this->belongsTo(Depart::class);
    }

    public function typeBillet(): BelongsTo
    {
        return $this->belongsTo(TypeBillet::class);
    }

    public function controleur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanne_par');
    }
}
