<?php

namespace App\Models;

use App\Enums\ControlePaiement;
use App\Enums\PaiementStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Paiement extends Model
{
    protected $fillable = [
        'reservation_id', 'transaction_id', 'montant_declare', 'montant_attendu',
        'quantite_deduite', 'controle', 'capture_path', 'capture_hash',
        'statut', 'motif_refus', 'verifie_par', 'verifie_at',
    ];

    protected function casts(): array
    {
        return [
            'montant_declare' => 'integer',
            'montant_attendu' => 'integer',
            'quantite_deduite' => 'integer',
            'controle' => ControlePaiement::class,
            'statut' => PaiementStatus::class,
            'verifie_at' => 'datetime',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function verificateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verifie_par');
    }
}
