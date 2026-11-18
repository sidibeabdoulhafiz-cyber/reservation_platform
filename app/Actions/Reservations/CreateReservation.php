<?php

namespace App\Actions\Reservations;

use App\Enums\ReservationStatus;
use App\Enums\ReservationType;
use App\Models\Depart;
use App\Models\DepartZone;
use App\Models\Reservation;
use App\Models\Setting;
use App\Models\TypeBillet;
use App\Models\User;
use App\Support\Codes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class CreateReservation
{
    private const MAX_A_PAYER = 2;

    public function handle(User $user, array $choix): Reservation
    {
        if (! $user->actif) {
            throw ValidationException::withMessages([
                'reservation' => 'Ce compte est désactivé. Contactez l\'organisation.',
            ]);
        }

        if ((string) Setting::get('site_ouvert', '1') !== '1') {
            throw ValidationException::withMessages([
                'reservation' => 'Les réservations sont fermées pour le moment.',
            ]);
        }

        $cle = 'reserver:'.$user->id;
        if (RateLimiter::tooManyAttempts($cle, 8)) {
            throw ValidationException::withMessages([
                'reservation' => 'Trop de tentatives. Attendez une minute avant de réessayer.',
            ]);
        }
        RateLimiter::hit($cle, 60);

        return DB::transaction(function () use ($user, $choix) {
            $enCours = $user->reservations()
                ->where('statut', ReservationStatus::APayer->value)
                ->lockForUpdate()
                ->count();

            if ($enCours >= self::MAX_A_PAYER) {
                throw ValidationException::withMessages([
                    'reservation' => 'Vous avez déjà '.self::MAX_A_PAYER.' réservations à payer. Réglez-les d\'abord.',
                ]);
            }

            $type = ReservationType::from($choix['type']);
            $quantite = (int) $choix['quantite'];

            if ($type === ReservationType::Transport) {
                [$depart, $tarif] = $this->offreTransport((int) $choix['depart_id'], (int) $choix['zone_id']);
                $this->verifierQuantite($quantite, (int) $depart->max_par_commande, 'places');
                $prix = (int) $tarif->prix;
                $payload = [
                    'depart_id' => $depart->id,
                    'zone_id' => $tarif->zone_id,
                    'type_billet_id' => null,
                ];
            } else {
                $offre = $this->offreBillet((int) $choix['type_billet_id']);
                $this->verifierQuantite($quantite, (int) $offre->max_par_commande, 'billets');
                $prix = (int) $offre->prix;
                $payload = [
                    'depart_id' => null,
                    'zone_id' => null,
                    'type_billet_id' => $offre->id,
                ];
            }

            $reservation = $user->reservations()->create([
                ...$payload,
                'ref' => Codes::reservation(),
                'type' => $type,
                'quantite' => $quantite,
                'passagers' => $this->passagers($choix['passagers'] ?? [], $quantite, $user),
                'prix_unitaire' => $prix,
                'montant' => $prix * $quantite,
                'statut' => ReservationStatus::APayer,
                'expire_at' => now()->addMinutes((int) Setting::get('delai_paiement_min', 30)),
            ]);

            app(GererPlaces::class)->bloquer($reservation);

            return $reservation;
        });
    }

    /** @return array{0: Depart, 1: DepartZone} */
    private function offreTransport(int $departId, int $zoneId): array
    {
        $depart = Depart::query()
            ->with('evenement')
            ->whereKey($departId)
            ->lockForUpdate()
            ->first();

        if (! $depart || $depart->statut !== 'ouvert' || $depart->evenement?->statut !== 'publie') {
            throw ValidationException::withMessages([
                'depart_id' => 'Ce départ n\'est plus disponible.',
            ]);
        }

        $tarif = DepartZone::query()
            ->where('depart_id', $depart->id)
            ->where('zone_id', $zoneId)
            ->where('actif', true)
            ->first();

        if (! $tarif) {
            throw ValidationException::withMessages([
                'zone_id' => 'Cette zone n\'est pas desservie pour ce départ.',
            ]);
        }

        return [$depart, $tarif];
    }

    private function offreBillet(int $typeBilletId): TypeBillet
    {
        $offre = TypeBillet::query()
            ->with('evenement')
            ->whereKey($typeBilletId)
            ->lockForUpdate()
            ->first();

        if (! $offre || ! $offre->enVente() || $offre->evenement?->statut !== 'publie') {
            throw ValidationException::withMessages([
                'type_billet_id' => 'Cette catégorie de billet n\'est plus en vente.',
            ]);
        }

        return $offre;
    }

    private function verifierQuantite(int $quantite, int $max, string $mot): void
    {
        if ($quantite < 1 || $quantite > $max) {
            throw ValidationException::withMessages([
                'quantite' => 'Choisissez entre 1 et '.$max.' '.$mot.' par commande.',
            ]);
        }
    }

    /**
     * Noms des passagers (invités sans compte autorisés). Un nom par place, 120 caractères max.
     *
     * @return list<string>
     */
    private function passagers(mixed $saisis, int $quantite, User $user): array
    {
        $saisis = array_values(is_array($saisis) ? $saisis : []);
        $noms = [];

        for ($i = 0; $i < $quantite; $i++) {
            $nom = trim(strip_tags((string) ($saisis[$i] ?? '')));
            $nom = mb_substr(preg_replace('/\s+/u', ' ', $nom) ?? '', 0, 120);
            $noms[] = $nom !== '' ? $nom : ($i === 0 ? $user->name : 'Passager '.($i + 1));
        }

        return $noms;
    }
}
