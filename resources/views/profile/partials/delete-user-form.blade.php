<section>
    <h2>Désactiver le compte</h2>
    <p class="hint">Le compte reste dans l’historique comptable. Vous ne pourrez plus réserver.</p>

    <form method="post" action="{{ route('profile.destroy') }}" style="margin-top:12px">
        @csrf
        @method('delete')

        <label>
            Mot de passe
            <input id="password" name="password" type="password" placeholder="Mot de passe">
        </label>
        <x-input-error :messages="$errors->userDeletion->get('password')" class="err" />

        <button class="cta" type="submit" style="margin-top:12px;background:#b42318">
            <span>Désactiver mon compte</span>
        </button>
    </form>
</section>
