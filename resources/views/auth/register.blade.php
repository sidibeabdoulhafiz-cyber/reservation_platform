<x-guest-layout>
    <header class="hero" style="margin-bottom:16px">
        <h1>Inscription</h1>
        <p>Un compte pour réserver, payer et recevoir vos billets.</p>
    </header>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <label>
            Nom
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
        </label>
        <x-input-error :messages="$errors->get('name')" class="err" />

        <label>
            Adresse e-mail
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
        </label>
        <x-input-error :messages="$errors->get('email')" class="err" />

        <label>
            Mot de passe
            <input id="password" type="password" name="password" required autocomplete="new-password">
        </label>
        <x-input-error :messages="$errors->get('password')" class="err" />

        <label>
            Confirmer le mot de passe
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
        </label>

        <button class="cta" type="submit"><span>Créer mon compte</span></button>
    </form>

    <p class="hint" style="margin-top:12px">
        <a href="{{ route('login') }}">J’ai déjà un compte</a>
    </p>
</x-guest-layout>
