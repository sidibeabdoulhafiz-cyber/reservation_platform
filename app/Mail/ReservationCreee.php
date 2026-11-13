<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationCreee extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Reservation $reservation) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Réservation '.$this->reservation->ref.' : finalise ton paiement');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.reservation-creee', with: [
            'r' => $this->reservation->loadMissing(['zone', 'depart.evenement', 'depart.tarifs', 'typeBillet.evenement', 'user']),
        ]);
    }
}
