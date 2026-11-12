<section>
    <h2>Mot de passe</h2>
    <p class="hint">Choisissez un mot de passe que vous seul connaissez.</p>

    <form method="post" action="{{ route('password.update') }}" style="margin-top:12px">
        @csrf
        @method('put')

        <label>
            Mot de passe actuel
            <input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password">
        </label>
        <x-input-error :messages="$errors->updatePassword->get('current_password')" class="err" />

        <label>
            Nouveau mot de passe
            <input id="update_password_password" name="password" type="password" autocomplete="new-password">
        </label>
        <x-input-error :messages="$errors->updatePassword->get('password')" class="err" />

        <label>
            Confirmer le mot de passe
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
        </label>
        <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="err" />

        <button class="cta" type="submit"><span>Mettre à jour le mot de passe</span></button>

        @if (session('status') === 'password-updated')
            <p class="hint note-ok">Enregistré.</p>
        @endif
    </form>
</section>
