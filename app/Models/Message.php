<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'type', 'nom', 'email', 'tel', 'organisation', 'evenement', 'date_evenement',
        'lieu', 'participants', 'sujet', 'message', 'statut', 'note_interne', 'ip',
    ];

    protected function casts(): array
    {
        return [
            'date_evenement' => 'date',
            'participants' => 'integer',
        ];
    }

    public function estOrganisateur(): bool
    {
        return $this->type === 'organisateur';
    }
}
