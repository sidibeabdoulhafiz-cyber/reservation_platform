<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\CodeConnexion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class CodeVerificationController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (! CodeConnexion::actif() || CodeConnexion::estValide($request, $user)) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $erreurEnvoi = false;
        try {
            CodeConnexion::envoyer($user);
        } catch (\Throwable $e) {
            report($e);
            $erreurEnvoi = true;
        }

        return view('auth.code', [
            'email' => $user->email,
            'nouveau' => ! $user->email_verified_at,
            'attente' => CodeConnexion::secondesAvantRenvoi($user),
            'erreurEnvoi' => $erreurEnvoi,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:12']], ['code.required' => 'Saisis le code à 6 chiffres reçu par e-mail.']);

        $user = $request->user();
        $cle = 'code-essai:'.$user->getKey().'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($cle, 10)) {
            return back()->withErrors(['code' => 'Trop d’essais. Réessaie dans quelques minutes.']);
        }
        RateLimiter::hit($cle, 600);

        $resultat = CodeConnexion::verifier($user, $request->string('code'));

        if ($resultat !== 'ok') {
            return back()->withErrors(['code' => match ($resultat) {
                'faux' => 'Code incorrect. Vérifie le dernier e-mail reçu.',
                'bloque' => 'Trop d’erreurs : demande un nouveau code.',
                default => 'Ce code a expiré : demande un nouveau code.',
            }]);
        }

        RateLimiter::clear($cle);
        CodeConnexion::valider($request, $user, $request->boolean('confiance'));

        return redirect()->intended(route('dashboard', absolute: false))
            ->with('status', $user->wasChanged('email_verified_at') ? 'Adresse e-mail confirmée. Bienvenue sur CotaTrans !' : null);
    }

    public function renvoyer(Request $request): RedirectResponse
    {
        try {
            $ok = CodeConnexion::envoyer($request->user(), forcer: true);
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['code' => 'L’e-mail n’a pas pu partir. Réessaie dans un instant.']);
        }

        return back()->with('status', $ok ? 'Nouveau code envoyé.' : 'Patiente une minute avant de redemander un code.');
    }
}
