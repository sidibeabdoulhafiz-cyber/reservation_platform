<?php

namespace App\Actions\Messages;

use App\Models\Evenement;
use App\Models\Message;
use Illuminate\Support\Str;

/**
 * Crée un événement en brouillon à partir d'une demande (organisateur ou contact).
 * L'admin le complète ensuite (affiche, départs, tarifs) avant de le publier.
 */
class CreerEvenementBrouillon
{
    public function handle(Message $message): Evenement
    {
        if ($message->evenementCree) {
            return $message->evenementCree;
        }

        $titre = Str::limit(trim($message->objet()), 150, '');
        $base = Str::slug($titre) ?: 'evenement';
        $slug = $base;
        $i = 2;
        while (Evenement::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        $debut = $message->date_evenement
            ? $message->date_evenement->copy()->setTime(20, 0)
            : now()->addDays(30)->setTime(20, 0);

        $description = collect([
            $message->message,
            $message->organisation ? 'Organisateur : '.$message->organisation : null,
            $message->participants ? 'Participants attendus : '.$message->participants : null,
        ])->filter()->implode("\n\n");

        $evenement = Evenement::create([
            'titre' => $titre,
            'slug' => $slug,
            'categorie' => self::devinerCategorie($titre.' '.$message->message),
            'description' => $description ?: null,
            'lieu' => $message->lieu ?: 'À préciser',
            'ville' => 'Abidjan',
            'debut_at' => $debut,
            'statut' => 'brouillon',
        ]);

        $message->update([
            'evenement_id' => $evenement->id,
            'statut' => $message->statut === 'nouveau' ? 'en_cours' : $message->statut,
        ]);

        return $evenement;
    }

    public static function devinerCategorie(string $texte): ?string
    {
        $t = Str::lower(Str::ascii($texte));

        return match (true) {
            Str::contains($t, ['match', 'foot', 'can ', 'stade', 'derby', 'basket']) => 'match',
            Str::contains($t, ['festival', 'fest ', 'femua']) => 'festival',
            Str::contains($t, ['concert', 'live', 'showcase', 'tour', 'spectacle']) => 'concert',
            Str::contains($t, ['soiree', 'journee', 'retro', 'plage', 'sortie', 'excursion', 'gala']) => 'sortie',
            default => null,
        };
    }
}
