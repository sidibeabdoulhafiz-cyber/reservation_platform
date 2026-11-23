<x-guest-layout title="Mot de passe oublié">
    <header class="hero">
        <h1>Mot de passe oublié ?</h1>
        <p>Indiquez l’e-mail de votre compte : nous vous envoyons un lien pour choisir un nouveau mot de passe.</p>
    </header>

    @if (session('status'))
        <div class="alert alert-ok" role="status"><x-ico name="check-circle" /> {{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <label>
            E-mail
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="awa@exemple.com">
        </label>
        <x-input-error :messages="$errors->get('email')" class="err" />

        <button class="cta" type="submit">Envoyer le lien</button>
    </form>

    <p class="hint" style="margin-top:14px;text-align:center">Pensez à vérifier vos spams. Le lien est valable 60 minutes.</p>
    <p class="auth-alt"><a href="{{ route('login') }}">← Retour à la connexion</a></p>
</x-guest-layout>
