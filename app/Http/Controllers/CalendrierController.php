<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/** Fichier .ics : ajoute le ramassage à l'agenda du téléphone (rappel 1 h avant). */
class CalendrierController extends Controller
{
    public function __invoke(Reservation $reservation): Response
    {
        Gate::authorize('view', $reservation);
        abort_unless($reservation->statut === ReservationStatus::Payee && $reservation->depart, 404);

        $depart = $reservation->depart->load(['evenement', 'tarifs']);
        $evt = $depart->evenement;
        $arret = $depart->tarifs->firstWhere('zone_id', $reservation->zone_id);

        $debut = $depart->depart_at->copy();
        if ($arret?->heure_ramassage) {
            [$h, $m] = array_map('intval', explode(':', (string) $arret->heure_ramassage));
            $debut->setTime($h, $m);
        }
        $fin = $depart->retour_at ?? $evt->fin_at ?? $debut->copy()->addHours(6);

        $lieu = trim(($reservation->zone?->nom ?? '').($arret?->point_rdv ? ' · '.$arret->point_rdv : ''));
        $description = 'Ramassage CotaTrans pour '.$evt->titre.' ('.$evt->lieu.'). '
            .$reservation->quantite.' passager(s). Présente le QR code de chaque billet à l’agent de bord. '
            .'Billets : '.route('reservations.show', $reservation);

        $ics = implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//CotaTrans//Billets//FR',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:reservation-'.$reservation->id.'@'.parse_url(config('app.url'), PHP_URL_HOST),
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.Carbon::parse($debut)->utc()->format('Ymd\THis\Z'),
            'DTEND:'.Carbon::parse($fin)->utc()->format('Ymd\THis\Z'),
            'SUMMARY:'.self::echapper('🚌 CotaTrans · '.$evt->titre),
            'LOCATION:'.self::echapper($lieu ?: $evt->lieu),
            'DESCRIPTION:'.self::echapper($description),
            'URL:'.route('reservations.show', $reservation),
            'BEGIN:VALARM',
            'TRIGGER:-PT1H',
            'ACTION:DISPLAY',
            'DESCRIPTION:'.self::echapper('Ramassage CotaTrans dans 1 heure'),
            'END:VALARM',
            'END:VEVENT',
            'END:VCALENDAR',
        ])."\r\n";

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="cotatrans-'.$evt->slug.'.ics"',
        ]);
    }

    private static function echapper(string $t): string
    {
        return str_replace(["\\", ';', ',', "\n"], ['\\\\', '\;', '\\,', '\\n'], $t);
    }
}
