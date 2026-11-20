<?php

namespace App\Livewire\Pages\Concerns;

use App\Mail\AccuseReception;
use App\Mail\NouveauMessage;
use App\Models\Message;
use App\Models\Setting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Throwable;

trait EnvoieMessage
{
    /** Champ piège anti-robots : doit rester vide. */
    public string $site_web = '';

    public bool $envoye = false;

    protected function enregistrerEtNotifier(array $donnees): Message
    {
        if ($this->site_web !== '') {
            // Robot : on fait comme si tout s'était bien passé.
            $this->envoye = true;

            return new Message($donnees);
        }

        $cle = 'message-site:'.request()->ip();
        if (RateLimiter::tooManyAttempts($cle, 5)) {
            throw ValidationException::withMessages([
                'message' => 'Trop d’envois en peu de temps. Réessaie dans '.ceil(RateLimiter::availableIn($cle) / 60).' minutes.',
            ]);
        }
        RateLimiter::hit($cle, 3600);

        $message = Message::create([...$donnees, 'ip' => request()->ip()]);

        $destinataire = Setting::get('email_reception') ?: config('mail.from.address');

        try {
            Mail::to($destinataire)->send(new NouveauMessage($message));
            Mail::to($message->email, $message->nom)->send(new AccuseReception($message));
        } catch (Throwable $e) {
            report($e); // Le message reste enregistré dans l'admin même si l'e-mail échoue.
        }

        $this->envoye = true;

        return $message;
    }
}
