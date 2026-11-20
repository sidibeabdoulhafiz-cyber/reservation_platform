<x-guest-layout title="Nouveau mot de passe">
    <header class="hero">
        <h1>Nouveau mot de passe</h1>
        <p>Choisis un mot de passe d’au moins 8 caractères.</p>
    </header>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <label>
            E-mail
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autocomplete="username">
        </label>
        <x-input-error :messages="$errors->get('email')" class="err" />

        <label>
            Nouveau mot de passe
            <input id="password" type="password" name="password" required autofocus autocomplete="new-password">
        </label>
        <x-input-error :messages="$errors->get('password')" class="err" />

        <label>
            Confirme le mot de passe
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
        </label>
        <x-input-error :messages="$errors->get('password_confirmation')" class="err" />

        <button class="cta" type="submit">Enregistrer le mot de passe</button>
    </form>
</x-guest-layout>
