<?php

namespace App\Mail;

use App\Models\Message;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Réponse de l'équipe CotaTrans à une demande d'organisateur ou à un message de contact. */
class ReponseMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Message $m, public string $sujet, public string $texte) {}

    public function envelope(): Envelope
    {
        $repondreA = Setting::get('email_reception') ?: config('mail.from.address');

        return new Envelope(
            subject: $this->sujet,
            replyTo: $repondreA ? [new Address($repondreA, 'CotaTrans')] : [],
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.reponse-message', with: ['m' => $this->m, 'texte' => $this->texte]);
    }
}
