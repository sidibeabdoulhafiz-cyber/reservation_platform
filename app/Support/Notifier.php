<?php

namespace App\Support;

use App\Mail\BilletsConfirmes;
use App\Mail\PaiementRecu;
use App\Mail\PaiementRefuse;
use App\Mail\RappelDepart;
use App\Mail\ReservationCreee;
use App\Models\Paiement;
use App\Models\Reservation;
use App\Models\Setting;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Envoie les e-mails transactionnels (Brevo en production).
 * Un échec d'envoi ne doit jamais bloquer une réservation : on journalise et on continue.
 */
final class Notifier
{
    /** Un e-mail facultatif peut être coupé dans Réglages pour économiser le quota d'envoi. */
    public static function actif(string $cle): bool
    {
        return (string) Setting::get($cle, '1') === '1';
    }

    public static function reservationCreee(Reservation $reservation): void
    {
        if (! self::actif('mail_reservation_creee')) {
            return;
        }
        self::envoyer($reservation, new ReservationCreee($reservation));
    }

    public static function paiementRecu(Paiement $paiement): void
    {
        if (! self::actif('mail_paiement_recu')) {
            return;
        }
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

    public static function rappelDepart(Reservation $reservation, string $moment): void
    {
        if (! self::actif($moment === 'veille' ? 'mail_rappel_veille' : 'mail_rappel_depart')) {
            return;
        }
        self::envoyer($reservation, new RappelDepart($reservation, $moment));
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
