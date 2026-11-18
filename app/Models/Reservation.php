<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use App\Enums\ReservationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model
{
    protected $fillable = [
        'ref', 'user_id', 'type', 'depart_id', 'zone_id', 'type_billet_id',
        'quantite', 'passagers', 'prix_unitaire', 'montant', 'statut', 'expire_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => ReservationType::class,
            'statut' => ReservationStatus::class,
            'quantite' => 'integer',
            'passagers' => 'array',
            'prix_unitaire' => 'integer',
            'montant' => 'integer',
            'expire_at' => 'datetime',
        ];
    }

    /**
     * Noms des passagers, un par place. Les noms manquants sont complétés par le nom du payeur
     * (passager 1) ou « Passager N ».
     *
     * @return list<string>
     */
    public function nomsPassagers(): array
    {
        $noms = array_values(array_map(fn ($n) => trim((string) $n), (array) ($this->passagers ?? [])));
        $payeur = $this->user?->name ?? 'Passager 1';

        $liste = [];
        for ($i = 0; $i < (int) $this->quantite; $i++) {
            $liste[] = ($noms[$i] ?? '') !== '' ? $noms[$i] : ($i === 0 ? $payeur : 'Passager '.($i + 1));
        }

        return $liste;
    }

    public function getRouteKeyName(): string
    {
        return 'ref';
    }

    public function evenement(): ?Evenement
    {
        return $this->depart?->evenement ?? $this->typeBillet?->evenement;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function depart(): BelongsTo
    {
        return $this->belongsTo(Depart::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function typeBillet(): BelongsTo
    {
        return $this->belongsTo(TypeBillet::class);
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class);
    }

    public function billets(): HasMany
    {
        return $this->hasMany(Billet::class);
    }

    public function maxParCommande(): int
    {
        if ($this->type === ReservationType::Transport) {
            return (int) $this->depart?->max_par_commande ?: 8;
        }

        return (int) $this->typeBillet?->max_par_commande ?: 2;
    }

    public function libelleOffre(): string
    {
        if ($this->type === ReservationType::Transport) {
            return ($this->depart?->evenement?->titre ?? 'Départ')
                .' · '.($this->zone?->nom ?? 'Zone');
        }

        return ($this->typeBillet?->evenement?->titre ?? 'Événement')
            .' · '.($this->typeBillet?->nom ?? 'Billet');
    }
}
