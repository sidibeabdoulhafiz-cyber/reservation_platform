<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Rappel avant le départ : « veille » (J-1) ou « depart » (H-2). */
class RappelDepart extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Reservation $reservation, public string $moment) {}

    public function envelope(): Envelope
    {
        $r = $this->reservation->loadMissing(['zone', 'depart.tarifs', 'depart.evenement']);
        $arret = $r->depart?->tarifs->firstWhere('zone_id', $r->zone_id);
        $heure = $arret?->heure() ?? $r->depart?->depart_at?->format('H\hi');

        return new Envelope(subject: $this->moment === 'veille'
            ? '🚌 Demain : départ à '.$heure.' depuis '.$r->zone?->nom.' · '.$r->evenement()?->titre
            : '⏰ Départ dans 2 h : rendez-vous à '.$heure.' · '.$r->zone?->nom);
    }

    public function content(): Content
    {
        $r = $this->reservation->loadMissing(['zone', 'billets', 'user', 'depart.tarifs', 'depart.evenement']);

        return new Content(markdown: 'mail.rappel-depart', with: [
            'r' => $r,
            'evt' => $r->evenement(),
            'arret' => $r->depart?->tarifs->firstWhere('zone_id', $r->zone_id),
            'moment' => $this->moment,
        ]);
    }
}
