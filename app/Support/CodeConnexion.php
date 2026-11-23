<?php

namespace App\Support;

use App\Mail\CodeVerification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Code à 6 chiffres envoyé par e-mail : vérifie l'adresse à l'inscription
 * et protège chaque connexion depuis un appareil inconnu.
 */
final class CodeConnexion
{
    public const SESSION = 'code_ok';

    public const COOKIE = 'ct_appareil';

    public const ESSAIS_MAX = 5;

    public const DELAI_RENVOI = 60;

    public static function actif(): bool
    {
        return (bool) config('cotatrans.code_connexion');
    }

    public static function estValide(Request $request, User $user): bool
    {
        if ($request->session()->get(self::SESSION) === $user->getKey()) {
            return true;
        }

        if ($user->email_verified_at && self::appareilConnu($request, $user)) {
            $request->session()->put(self::SESSION, $user->getKey());

            return true;
        }

        return false;
    }

    /** Envoie un nouveau code (sauf si un code vient d'être envoyé). Retourne false si trop tôt. */
    public static function envoyer(User $user, bool $forcer = false): bool
    {
        $cle = self::cle($user);
        $actuel = Cache::get($cle);

        if ($actuel && ! $forcer) {
            return true;
        }
        if ($actuel && now()->timestamp - $actuel['envoye'] < self::DELAI_RENVOI) {
            return false;
        }

        $code = (string) random_int(100000, 999999);
        Cache::put($cle, [
            'hash' => Hash::make($code),
            'essais' => 0,
            'envoye' => now()->timestamp,
        ], now()->addMinutes(config('cotatrans.code_validite_minutes', 10)));

        Mail::to($user->email, $user->name)->send(new CodeVerification($user, $code));

        return true;
    }

    /** @return 'ok'|'faux'|'expire'|'bloque' */
    public static function verifier(User $user, string $saisie): string
    {
        $cle = self::cle($user);
        $actuel = Cache::get($cle);

        if (! $actuel) {
            return 'expire';
        }
        if ($actuel['essais'] >= self::ESSAIS_MAX) {
            return 'bloque';
        }

        if (! Hash::check(preg_replace('/\D/', '', $saisie), $actuel['hash'])) {
            $actuel['essais']++;
            Cache::put($cle, $actuel, now()->addMinutes(config('cotatrans.code_validite_minutes', 10)));

            return $actuel['essais'] >= self::ESSAIS_MAX ? 'bloque' : 'faux';
        }

        Cache::forget($cle);

        return 'ok';
    }

    public static function valider(Request $request, User $user, bool $faireConfiance): void
    {
        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $request->session()->put(self::SESSION, $user->getKey());

        if ($faireConfiance) {
            $jours = (int) config('cotatrans.appareil_confiance_jours', 30);
            Cookie::queue(Cookie::make(self::COOKIE, self::empreinte($user, now()->addDays($jours)->timestamp), $jours * 24 * 60, null, null, $request->isSecure(), true, false, 'lax'));
        }
    }

    public static function secondesAvantRenvoi(User $user): int
    {
        $actuel = Cache::get(self::cle($user));

        return $actuel ? max(0, self::DELAI_RENVOI - (now()->timestamp - $actuel['envoye'])) : 0;
    }

    private static function appareilConnu(Request $request, User $user): bool
    {
        $valeur = (string) $request->cookie(self::COOKIE);
        $parts = explode('|', $valeur);
        if (count($parts) !== 3 || (int) $parts[0] !== $user->getKey() || (int) $parts[1] < now()->timestamp) {
            return false;
        }

        return hash_equals(self::empreinte($user, (int) $parts[1]), $valeur);
    }

    /** Lié au mot de passe : changer de mot de passe révoque tous les appareils de confiance. */
    private static function empreinte(User $user, int $expire): string
    {
        $sig = hash_hmac('sha256', $user->getKey().'|'.$expire.'|'.$user->getAuthPassword(), (string) config('app.key'));

        return $user->getKey().'|'.$expire.'|'.$sig;
    }

    private static function cle(User $user): string
    {
        return 'code-connexion:'.$user->getKey();
    }
}
