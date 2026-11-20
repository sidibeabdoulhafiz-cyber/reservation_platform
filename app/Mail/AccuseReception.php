<?php

namespace App\Mail;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Accusé de réception envoyé à l'organisateur / à la personne qui nous écrit. */
class AccuseReception extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Message $m) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->m->estOrganisateur()
            ? 'CotaTrans a bien reçu ta demande pour « '.$this->m->evenement.' »'
            : 'CotaTrans a bien reçu ton message');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.accuse-reception', with: ['m' => $this->m]);
    }
}
