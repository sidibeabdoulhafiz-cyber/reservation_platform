<section>
    <h2>Désactiver le compte</h2>
    <p class="hint">Tes réservations restent dans l’historique, mais tu ne pourras plus te connecter ni réserver.</p>

    <form method="post" action="{{ route('profile.destroy') }}" style="margin-top:12px">
        @csrf
        @method('delete')

        <label>
            Mot de passe
            <input id="password" name="password" type="password" placeholder="Mot de passe">
        </label>
        <x-input-error :messages="$errors->userDeletion->get('password')" class="err" />

        <button class="cta" type="submit" style="margin-top:12px">
            <span>Désactiver mon compte</span>
        </button>
    </form>
</section>
