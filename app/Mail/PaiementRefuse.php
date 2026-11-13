<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaiementRefuse extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Reservation $reservation, public string $motif) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Action requise : ton reçu n’a pas pu être validé ('.$this->reservation->ref.')');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.paiement-refuse', with: [
            'r' => $this->reservation->loadMissing(['zone', 'depart.evenement', 'typeBillet.evenement', 'user']),
            'motif' => $this->motif,
        ]);
    }
}
