<?php

namespace App\Actions\Messages;

use App\Mail\ReponseMessage;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/** Envoie la réponse de l'équipe par e-mail et la garde dans l'historique du message. */
class RepondreMessage
{
    public function handle(Message $message, string $sujet, string $texte, ?string $statut = null, ?User $par = null): void
    {
        Mail::to($message->email, $message->nom)->send(new ReponseMessage($message, $sujet, $texte));

        $message->ajouterReponse($sujet, $texte, $par?->name);

        if ($statut && $statut !== $message->statut) {
            $message->update(['statut' => $statut]);
        }
    }

    /** Texte proposé par défaut dans la fenêtre « Répondre ». */
    public static function modele(Message $m): string
    {
        $prenom = (string) str($m->nom)->before(' ');

        if ($m->estOrganisateur()) {
            return "Bonjour {$prenom},\n\n"
                ."Merci pour votre demande de transport pour « {$m->evenement} ».\n\n"
                ."Nous pouvons organiser l’aller-retour de votre public avec les éléments suivants :\n"
                ."- Lignes et arrêts proposés : …\n"
                ."- Nombre de cars / places : …\n"
                ."- Tarif aller-retour par passager : … FCFA\n\n"
                ."Dès votre accord, nous publions la page de réservation et vous envoyons le lien à partager avec votre public.\n\n"
                ."Bien cordialement,";
        }

        return "Bonjour {$prenom},\n\nMerci pour votre message.\n\n…\n\nBien cordialement,";
    }
}
