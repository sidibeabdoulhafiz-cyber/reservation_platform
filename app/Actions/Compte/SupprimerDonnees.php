<?php

namespace App\Actions\Compte;

use App\Enums\ReservationStatus;
use App\Models\Billet;
use App\Models\Message;
use App\Models\Paiement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Suppression des données personnelles d'un utilisateur (droit à l'effacement).
 *
 * Le compte est anonymisé plutôt que supprimé : les réservations et paiements restent
 * dans la comptabilité, mais sans aucune donnée permettant d'identifier la personne
 * (nom, e-mail, téléphone, noms des passagers, captures de reçus).
 */
class SupprimerDonnees
{
    public function handle(User $user): void
    {
        $bloquantes = $user->reservations()
            ->whereIn('statut', [ReservationStatus::APayer->value, ReservationStatus::EnVerification->value])
            ->count()
            + $user->reservations()
                ->where('statut', ReservationStatus::Payee->value)
                ->whereHas('depart', fn ($q) => $q->where(fn ($w) => $w->where('retour_at', '>', now())->orWhere(fn ($x) => $x->whereNull('retour_at')->where('depart_at', '>', now()->subHours(12)))))
                ->count();

        if ($bloquantes > 0) {
            throw ValidationException::withMessages([
                'password' => 'Tu as une réservation en cours ou un voyage à venir. Tu pourras supprimer tes données après ton retour, ou annule d’abord la réservation non payée.',
            ])->errorBag('userDeletion');
        }

        $email = $user->email;
        $reservationIds = $user->reservations()->pluck('id');

        // Fichiers : captures de reçus (données personnelles possibles : nom, numéro).
        $captures = Paiement::whereIn('reservation_id', $reservationIds)->whereNotNull('capture_path')->pluck('capture_path');

        DB::transaction(function () use ($user, $email, $reservationIds) {
            $user->reservations()->update(['passagers' => null]);
            Billet::whereIn('reservation_id', $reservationIds)->update(['nom_passager' => 'Passager (données supprimées)']);
            Paiement::whereIn('reservation_id', $reservationIds)->update(['capture_path' => null]);

            Message::where('email', $email)->update([
                'nom' => 'Anonyme', 'email' => 'supprime@anonyme.invalid', 'tel' => null, 'message' => null, 'ip' => null,
            ]);

            DB::table('sessions')->where('user_id', $user->id)->delete();
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            $user->forceFill([
                'name' => 'Compte supprimé',
                'email' => 'supprime-'.$user->id.'-'.Str::lower(Str::random(6)).'@anonyme.invalid',
                'tel' => null,
                'password' => Hash::make(Str::random(40)),
                'remember_token' => null,
                'email_verified_at' => null,
                'actif' => false,
            ])->save();
        });

        foreach ($captures as $chemin) {
            Storage::disk('local')->delete($chemin);
        }
    }

    /** Export lisible des données personnelles (droit d'accès / portabilité). */
    public function exporter(User $user): array
    {
        $user->loadMissing(['reservations.zone', 'reservations.billets', 'reservations.depart.evenement', 'reservations.typeBillet.evenement', 'reservations.paiements']);

        return [
            'exporte_le' => now()->toIso8601String(),
            'compte' => [
                'nom' => $user->name,
                'email' => $user->email,
                'telephone' => $user->tel,
                'cree_le' => $user->created_at?->toIso8601String(),
            ],
            'reservations' => $user->reservations->map(fn ($r) => [
                'reference' => $r->ref,
                'evenement' => $r->evenement()?->titre,
                'arret' => $r->zone?->nom,
                'statut' => $r->statut->value,
                'places' => $r->quantite,
                'montant_fcfa' => $r->montant,
                'passagers' => $r->nomsPassagers(),
                'billets' => $r->billets->map(fn ($b) => ['numero' => $b->numero, 'passager' => $b->nom_passager, 'statut' => $b->statut->value])->all(),
                'paiements' => $r->paiements->map(fn ($p) => ['transaction' => $p->transaction_id, 'montant' => $p->montant_declare, 'statut' => $p->statut->value, 'date' => $p->created_at?->toIso8601String()])->all(),
                'cree_le' => $r->created_at?->toIso8601String(),
            ])->all(),
            'messages_envoyes' => Message::where('email', $user->email)->get(['type', 'sujet', 'evenement', 'message', 'created_at'])->toArray(),
        ];
    }
}
