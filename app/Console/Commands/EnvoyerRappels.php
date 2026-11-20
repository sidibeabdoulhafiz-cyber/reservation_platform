<?php

namespace App\Console\Commands;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Support\Notifier;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class EnvoyerRappels extends Command
{
    protected $signature = 'reservations:rappels';

    protected $description = 'Envoie les rappels de départ J-1 (la veille) et H-2 (deux heures avant) aux passagers confirmés';

    public function handle(): int
    {
        // J-1 : départs dans 20 h à 28 h.
        $veille = $this->aRappeler('rappel_veille_at', now()->addHours(20), now()->addHours(28));
        foreach ($veille as $r) {
            Notifier::rappelDepart($r, 'veille');
            $r->forceFill(['rappel_veille_at' => now()])->save();
        }

        // H-2 : départs dans 0 à 2 h 15 (marge pour la fréquence de la tâche planifiée).
        $depart = $this->aRappeler('rappel_depart_at', now(), now()->addMinutes(135));
        foreach ($depart as $r) {
            Notifier::rappelDepart($r, 'depart');
            $r->forceFill(['rappel_depart_at' => now()])->save();
        }

        $this->info("Rappels envoyés : {$veille->count()} la veille, {$depart->count()} avant départ.");

        return self::SUCCESS;
    }

    private function aRappeler(string $colonne, $debut, $fin)
    {
        return Reservation::query()
            ->with(['user', 'zone', 'billets', 'depart.tarifs', 'depart.evenement'])
            ->where('statut', ReservationStatus::Payee->value)
            ->whereNull($colonne)
            ->whereHas('depart', fn (Builder $q) => $q->whereBetween('depart_at', [$debut, $fin]))
            ->get();
    }
}
