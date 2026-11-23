<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'type', 'nom', 'email', 'tel', 'organisation', 'evenement', 'date_evenement',
        'lieu', 'participants', 'sujet', 'message', 'statut', 'note_interne', 'ip', 'reponses', 'repondu_at', 'evenement_id',
    ];

    protected function casts(): array
    {
        return [
            'date_evenement' => 'date',
            'participants' => 'integer',
            'reponses' => 'array',
            'repondu_at' => 'datetime',
        ];
    }

    public function evenementCree(): BelongsTo
    {
        return $this->belongsTo(Evenement::class, 'evenement_id');
    }

    /** Objet lisible : nom de l'événement proposé ou sujet du message. */
    public function objet(): string
    {
        return (string) ($this->estOrganisateur() ? $this->evenement : $this->sujet) ?: 'Votre message';
    }

    public function ajouterReponse(string $sujet, string $texte, ?string $par): void
    {
        $this->reponses = [...($this->reponses ?? []), [
            'le' => now()->toIso8601String(),
            'par' => $par,
            'sujet' => $sujet,
            'texte' => $texte,
        ]];
        $this->repondu_at = now();
        $this->save();
    }

    public function estOrganisateur(): bool
    {
        return $this->type === 'organisateur';
    }
}
