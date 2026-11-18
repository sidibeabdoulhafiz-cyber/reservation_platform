<x-guest-layout>
    <header class="hero" style="margin-bottom:16px">
        <h1>Crée ton compte</h1>
        <p>Il te servira à réserver, payer et retrouver tes billets.</p>
    </header>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <label>
            Nom complet
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
        </label>
        <x-input-error :messages="$errors->get('name')" class="err" />

        <label>
            Téléphone (WhatsApp)
            <input id="tel" type="tel" name="tel" value="{{ old('tel') }}" required inputmode="tel" autocomplete="tel" placeholder="07 00 00 00 00">
        </label>
        <x-input-error :messages="$errors->get('tel')" class="err" />

        <label>
            Adresse e-mail
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
        </label>
        <x-input-error :messages="$errors->get('email')" class="err" />

        <label>
            Mot de passe
            <input id="password" type="password" name="password" required autocomplete="new-password" placeholder="8 caractères minimum">
        </label>
        <x-input-error :messages="$errors->get('password')" class="err" />

        <label>
            Confirmer le mot de passe
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
        </label>

        <button class="cta" type="submit"><span>Créer mon compte</span></button>
    </form>

    <p class="auth-alt">Déjà un compte ? <a href="{{ route('login') }}">Se connecter</a></p>
</x-guest-layout>
