<?php

namespace App\Support;

use App\Mail\BilletsConfirmes;
use App\Mail\PaiementRecu;
use App\Mail\PaiementRefuse;
use App\Mail\ReservationCreee;
use App\Models\Paiement;
use App\Models\Reservation;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Envoie les e-mails transactionnels (Brevo en production).
 * Un échec d'envoi ne doit jamais bloquer une réservation : on journalise et on continue.
 */
final class Notifier
{
    public static function reservationCreee(Reservation $reservation): void
    {
        self::envoyer($reservation, new ReservationCreee($reservation));
    }

    public static function paiementRecu(Paiement $paiement): void
    {
        self::envoyer($paiement->reservation, new PaiementRecu($paiement->reservation));
    }

    public static function billetsConfirmes(Reservation $reservation): void
    {
        self::envoyer($reservation, new BilletsConfirmes($reservation));
    }

    public static function paiementRefuse(Reservation $reservation, string $motif): void
    {
        self::envoyer($reservation, new PaiementRefuse($reservation, $motif));
    }

    private static function envoyer(Reservation $reservation, Mailable $mail): void
    {
        $user = $reservation->user;

        if (! $user?->email) {
            return;
        }

        try {
            $envoi = Mail::to($user->email, $user->name);
            config('mail.en_file') ? $envoi->queue($mail) : $envoi->send($mail);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
