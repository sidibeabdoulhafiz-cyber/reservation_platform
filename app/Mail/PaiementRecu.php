<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaiementRecu extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Reservation $reservation) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Reçu bien reçu : vérification en cours ('.$this->reservation->ref.')');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.paiement-recu', with: [
            'r' => $this->reservation->loadMissing(['zone', 'depart.evenement', 'typeBillet.evenement', 'user']),
        ]);
    }
}
