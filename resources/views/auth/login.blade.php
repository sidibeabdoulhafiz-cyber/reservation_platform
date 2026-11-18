<x-guest-layout>
    <header class="hero" style="margin-bottom:16px">
        <h1>Bon retour !</h1>
        <p>Connecte-toi pour réserver et retrouver tes billets. Ton choix de places est conservé.</p>
    </header>

    <x-auth-session-status class="alert alert-ok" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <label>
            Adresse e-mail
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
        </label>
        <x-input-error :messages="$errors->get('email')" class="err" />

        <label>
            Mot de passe
            <input id="password" type="password" name="password" required autocomplete="current-password">
        </label>
        <x-input-error :messages="$errors->get('password')" class="err" />

        <label class="check">
            <input id="remember_me" type="checkbox" name="remember">
            Se souvenir de moi
        </label>

        <button class="cta" type="submit"><span>Se connecter</span></button>
    </form>

    @if (! app()->environment('production'))
        <p class="hint" style="margin-top:16px;font-size:13px">
            Local, sans HTTPS — mot de passe <span class="mono">password</span><br>
            Client : client@cotatrans.test · Admin : admin@cotatrans.test · Contrôle : controle@cotatrans.test
        </p>
    @endif

    @if (Route::has('password.request'))
        <p class="hint" style="text-align:center;margin-top:14px"><a href="{{ route('password.request') }}">Mot de passe oublié ?</a></p>
    @endif
    <p class="auth-alt">Pas encore de compte ? <a href="{{ route('register') }}">Créer un compte</a></p>
</x-guest-layout>
