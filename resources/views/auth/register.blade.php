<x-guest-layout title="Créer un compte">
    <header class="hero auth-head">
        <h1>Créer un compte</h1>
        <p>Pour réserver et recevoir vos billets.</p>
    </header>

    <form method="POST" action="{{ route('register') }}" class="auth-form">
        @csrf

        <label for="name">Nom et prénom</label>
        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Ex. Awa Koné">
        <x-input-error :messages="$errors->get('name')" class="err" />

        <label for="tel">Téléphone WhatsApp</label>
        <input id="tel" type="tel" name="tel" value="{{ old('tel') }}" required inputmode="tel" autocomplete="tel" placeholder="07 00 00 00 00">
        <x-input-error :messages="$errors->get('tel')" class="err" />

        <label for="email">Adresse e-mail</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" inputmode="email" placeholder="vous@exemple.com">
        <p class="field-hint">Vos billets y seront envoyés.</p>
        <x-input-error :messages="$errors->get('email')" class="err" />

        <label for="password">Mot de passe</label>
        <input id="password" type="password" name="password" required autocomplete="new-password" minlength="8">
        <p class="field-hint">8 caractères minimum, avec au moins un chiffre.</p>
        <x-input-error :messages="$errors->get('password')" class="err" />

        <button class="cta" type="submit">Créer mon compte</button>
    </form>

    <p class="auth-alt">Déjà un compte ? <a href="{{ route('login') }}">Se connecter</a></p>
</x-guest-layout>
