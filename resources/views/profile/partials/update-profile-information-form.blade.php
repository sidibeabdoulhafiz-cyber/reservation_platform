<section>
    <h2>Informations</h2>
    <p class="hint">Nom et adresse e-mail du compte.</p>

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

        <button class="cta" type="submit"><span>Enregistrer</span></button>

        @if (session('status') === 'profile-updated')
            <p class="hint note-ok">Enregistré.</p>
        @endif
    </form>
</section>
