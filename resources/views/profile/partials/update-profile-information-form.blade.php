<section>
    <h2>Informations</h2>
    <p class="hint">Ces coordonnées servent à t’envoyer tes billets et les infos de départ.</p>

    <form method="post" action="{{ route('profile.update') }}" style="margin-top:12px">
        @csrf
        @method('patch')

        <label>
            Nom
            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autocomplete="name">
        </label>
        <x-input-error class="err" :messages="$errors->get('name')" />

        <label>
            Adresse e-mail
            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username">
        </label>
        <x-input-error class="err" :messages="$errors->get('email')" />

        <label>
            Téléphone (WhatsApp)
            <input id="tel" name="tel" type="tel" inputmode="tel" value="{{ old('tel', $user->tel) }}" autocomplete="tel" placeholder="07 00 00 00 00">
        </label>
        <x-input-error class="err" :messages="$errors->get('tel')" />

        <button class="cta" type="submit"><span>Enregistrer</span></button>

        @if (session('status') === 'profile-updated')
            <p class="alert alert-ok" style="margin:12px 0 0">Modifications enregistrées.</p>
        @endif
    </form>
</section>
