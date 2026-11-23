<x-guest-layout title="Connexion">
    <header class="hero auth-head">
        <h1>Connexion</h1>
        <p>Retrouvez vos réservations et vos billets.</p>
    </header>

    <x-auth-session-status class="alert alert-ok" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="auth-form">
        @csrf

        <label for="email">Adresse e-mail</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" inputmode="email" placeholder="vous@exemple.com">
        <x-input-error :messages="$errors->get('email')" class="err" />

        <div class="label-row">
            <label for="password">Mot de passe</label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}">Oublié ?</a>
            @endif
        </div>
        <input id="password" type="password" name="password" required autocomplete="current-password">
        <x-input-error :messages="$errors->get('password')" class="err" />

        <label class="check">
            <input id="remember_me" type="checkbox" name="remember" checked>
            <span>Rester connecté</span>
        </label>

        <button class="cta" type="submit">Se connecter</button>
    </form>

    <p class="auth-alt">Pas encore de compte ? <a href="{{ route('register') }}">Créer un compte</a></p>
</x-guest-layout>
