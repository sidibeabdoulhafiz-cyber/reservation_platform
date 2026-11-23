<?php

namespace App\Mail;

use App\Models\Reservation;
use App\Support\QrCode;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BilletsConfirmes extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Reservation $reservation) {}

    public function envelope(): Envelope
    {
        $evt = $this->reservation->evenement();

        return new Envelope(subject: 'Vos billets pour '.($evt?->titre ?? 'votre événement').' ('.$this->reservation->ref.')');
    }

    public function content(): Content
    {
        $r = $this->reservation->loadMissing(['zone', 'billets', 'depart.evenement', 'depart.tarifs', 'typeBillet.evenement', 'user']);

        return new Content(markdown: 'mail.billets-confirmes', with: [
            'r' => $r,
            'evt' => $r->evenement(),
            'arret' => $r->depart?->tarifs->firstWhere('zone_id', $r->zone_id),
        ]);
    }

    /** Un QR code PNG par billet, à présenter même sans connexion. */
    public function attachments(): array
    {
        return $this->reservation->billets
            ->map(function ($billet) {
                $png = QrCode::png($billet->numero);

                return $png === null ? null : Attachment::fromData(fn () => $png, 'billet-'.$billet->numero.'.png')
                    ->withMime('image/png');
            })
            ->filter()
            ->values()
            ->all();
    }
}
