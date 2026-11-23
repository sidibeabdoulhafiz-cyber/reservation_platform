<?php

namespace App\Http\Middleware;

use App\Support\CodeConnexion;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Tant que le code reçu par e-mail n'est pas saisi, l'utilisateur connecté est renvoyé vers la page du code. */
class ExigerCodeEmail
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Compte désactivé ou supprimé : déconnexion immédiate.
        if ($user && ! $user->actif) {
            auth()->guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'Ce compte est désactivé. Contacte CotaTrans si besoin.']);
        }

        if (! $user || ! CodeConnexion::actif() || CodeConnexion::estValide($request, $user)) {
            return $next($request);
        }

        if ($request->isMethod('GET') && ! $request->expectsJson()) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return $request->expectsJson()
            ? response()->json(['message' => 'Code de vérification requis.'], 403)
            : redirect()->route('code.show');
    }
}
