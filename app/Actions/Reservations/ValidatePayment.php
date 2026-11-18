<?php

namespace App\Actions\Reservations;

use App\Enums\BilletStatus;
use App\Enums\PaiementStatus;
use App\Enums\ReservationStatus;
use App\Enums\ReservationType;
use App\Models\Billet;
use App\Models\Paiement;
use App\Models\Reservation;
use App\Models\User;
use App\Support\Codes;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ValidatePayment
{
    public function handle(Paiement $paiement, User $admin, ?int $placesAValider = null): Reservation
    {
        abort_unless($admin->estAdmin(), 403);

        return DB::transaction(function () use ($paiement, $admin, $placesAValider) {
            $lockedPaiement = Paiement::whereKey($paiement->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedPaiement->statut !== PaiementStatus::EnVerification) {
                throw ValidationException::withMessages([
                    'paiement' => 'Ce paiement a déjà été traité.',
                ]);
            }

            $reservation = Reservation::whereKey($lockedPaiement->reservation_id)->lockForUpdate()->firstOrFail();

            if ($reservation->statut !== ReservationStatus::EnVerification) {
                throw ValidationException::withMessages([
                    'paiement' => 'Cette réservation n\'est plus en vérification.',
                ]);
            }

            $dejaValide = $reservation->paiements()
                ->where('statut', PaiementStatus::Valide->value)
                ->exists();

            if ($dejaValide) {
                throw ValidationException::withMessages([
                    'paiement' => 'Un paiement a déjà été validé pour cette réservation.',
                ]);
            }

            $maxPaye = (int) $lockedPaiement->quantite_deduite;
            $demande = $placesAValider ?? min($maxPaye, (int) $reservation->quantite);

            if ($demande < 1 || $demande > $maxPaye) {
                throw ValidationException::withMessages([
                    'places' => 'Vous ne pouvez valider qu\'entre 1 et '.$maxPaye.' place(s) d\'après le montant déclaré.',
                ]);
            }

            $places = app(GererPlaces::class);

            if ($demande < $reservation->quantite) {
                $places->liberer($reservation, $reservation->quantite - $demande);
                $reservation->quantite = $demande;
                $reservation->montant = $reservation->prix_unitaire * $demande;
            }

            if ($demande > $reservation->quantite) {
                $extra = $demande - $reservation->quantite;
                $places->bloquer($reservation, $extra);
                $reservation->quantite = $demande;
                $reservation->montant = $reservation->prix_unitaire * $demande;
            }

            $this->emettreBillets($reservation);

            $reservation->statut = ReservationStatus::Payee;
            $reservation->expire_at = null;
            $reservation->save();

            $lockedPaiement->update([
                'statut' => PaiementStatus::Valide,
                'verifie_par' => $admin->id,
                'verifie_at' => now(),
            ]);

            return $reservation->fresh(['billets']);
        });
    }

    private function emettreBillets(Reservation $reservation): void
    {
        $prefixe = $reservation->type->prefixBillet();
        $sieges = [];

        if ($reservation->type === ReservationType::Transport) {
            $pris = Billet::query()
                ->where('depart_id', $reservation->depart_id)
                ->whereNotNull('siege')
                ->lockForUpdate()
                ->pluck('siege')
                ->all();

            $occupes = array_flip($pris);
            $depart = $reservation->depart()->lockForUpdate()->first();
            $capacite = (int) ($depart?->places_total ?? 0);

            for ($s = 1; $s <= $capacite && count($sieges) < $reservation->quantite; $s++) {
                if (! isset($occupes[$s])) {
                    $sieges[] = $s;
                }
            }

            if (count($sieges) < $reservation->quantite) {
                throw ValidationException::withMessages([
                    'places' => 'Impossible d\'attribuer assez de sièges sur ce véhicule.',
                ]);
            }
        }

        $noms = $reservation->nomsPassagers();

        for ($i = 0; $i < $reservation->quantite; $i++) {
            $reservation->billets()->create([
                'numero' => Codes::billet($prefixe),
                'depart_id' => $reservation->depart_id,
                'type_billet_id' => $reservation->type_billet_id,
                'siege' => $sieges[$i] ?? null,
                'nom_passager' => $noms[$i] ?? $reservation->user?->name,
                'statut' => BilletStatus::Valide,
            ]);
        }
    }
}
