<?php

namespace App\Mail;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Notification interne : nouvelle demande d'organisateur ou nouveau message. Répondre = répondre à l'expéditeur. */
class NouveauMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Message $m) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->m->estOrganisateur()
                ? '📅 Nouvelle demande organisateur : '.$this->m->evenement
                : '✉️ Nouveau message : '.($this->m->sujet ?: $this->m->nom),
            replyTo: [new Address($this->m->email, $this->m->nom)],
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.nouveau-message', with: ['m' => $this->m]);
    }
}
