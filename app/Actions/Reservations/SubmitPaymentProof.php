<?php

namespace App\Actions\Reservations;

use App\Enums\PaiementStatus;
use App\Enums\ReservationStatus;
use App\Models\Paiement;
use App\Models\Reservation;
use App\Support\AnalyseMontant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class SubmitPaymentProof
{
    public function handle(Reservation $reservation, int $montantDeclare, string $transactionId, UploadedFile $capture): Paiement
    {
        $cle = 'preuve:'.$reservation->user_id;
        if (RateLimiter::tooManyAttempts($cle, 10)) {
            throw ValidationException::withMessages([
                'transaction_id' => 'Trop d\'envois. Attendez une minute.',
            ]);
        }
        RateLimiter::hit($cle, 60);

        $transactionId = strtoupper(trim($transactionId));
        $hash = hash_file('sha256', $capture->getRealPath());

        return DB::transaction(function () use ($reservation, $montantDeclare, $transactionId, $capture, $hash) {
            $locked = Reservation::whereKey($reservation->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->statut !== ReservationStatus::APayer) {
                throw ValidationException::withMessages([
                    'transaction_id' => 'Cette réservation n\'attend plus de paiement.',
                ]);
            }

            if (Paiement::where('transaction_id', $transactionId)->exists()) {
                throw ValidationException::withMessages([
                    'transaction_id' => 'Cet identifiant de transaction a déjà été utilisé.',
                ]);
            }

            $hashPris = Paiement::query()
                ->where('capture_hash', $hash)
                ->where('statut', '!=', PaiementStatus::Refuse->value)
                ->exists();

            if ($hashPris) {
                throw ValidationException::withMessages([
                    'capture' => 'Cette capture a déjà été envoyée pour un autre paiement.',
                ]);
            }

            $analyse = AnalyseMontant::analyser(
                $montantDeclare,
                (int) $locked->prix_unitaire,
                (int) $locked->quantite,
                $locked->maxParCommande(),
            );

            if (! $analyse['accepte']) {
                throw ValidationException::withMessages([
                    'montant_declare' => $analyse['message'],
                ]);
            }

            $paiement = $locked->paiements()->create([
                'transaction_id' => $transactionId,
                'montant_declare' => $montantDeclare,
                'montant_attendu' => $locked->montant,
                'quantite_deduite' => $analyse['places'],
                'controle' => $analyse['controle'],
                'capture_path' => $capture->store('captures', 'local'),
                'capture_hash' => $hash,
                'statut' => PaiementStatus::EnVerification,
            ]);

            $locked->update([
                'statut' => ReservationStatus::EnVerification,
                'expire_at' => null,
            ]);

            return $paiement;
        });
    }
}
