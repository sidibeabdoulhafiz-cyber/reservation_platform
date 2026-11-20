<section>
    <h2>Mes données personnelles</h2>
    <p class="hint">Tu gardes le contrôle sur tes informations.</p>

    <div class="data-actions">
        <a href="{{ route('profile.export') }}" class="btn btn-ghost btn-sm"><x-ico name="arrow-right" size="16" style="transform:rotate(90deg)" /> Télécharger mes données</a>
        @if (\App\Models\Setting::get('ga4_id') || \App\Models\Setting::get('meta_pixel_id'))
            <button type="button" class="btn btn-ghost btn-sm" onclick="window.dispatchEvent(new CustomEvent('ct-cookies'))">Gérer mes cookies</button>
        @else
            <span class="hint" style="margin:0">Aucun cookie de suivi n’est utilisé, seulement ceux indispensables à la connexion.</span>
        @endif
    </div>

    <details class="delete-box" @if ($errors->userDeletion->isNotEmpty()) open @endif>
        <summary>Supprimer mon compte et mes données</summary>
        <p class="hint">Ton nom, ton e-mail, ton téléphone, les noms de tes passagers et les captures de reçus seront <b>définitivement effacés</b>. Les montants payés restent dans la comptabilité, de façon anonyme. Cette action est irréversible.</p>
        <p class="hint">Impossible tant qu’une réservation est en cours ou qu’un voyage est à venir.</p>

        <form method="post" action="{{ route('profile.destroy') }}" style="margin-top:12px">
            @csrf
            @method('delete')

            <label>
                Mot de passe
                <input id="password_suppression" name="password" type="password" autocomplete="current-password" placeholder="Pour confirmer, saisis ton mot de passe">
            </label>
            <x-input-error :messages="$errors->userDeletion->get('password')" class="err" />

            <label class="check">
                <input type="checkbox" name="confirmation" value="1">
                Je comprends que la suppression est définitive.
            </label>
            <x-input-error :messages="$errors->userDeletion->get('confirmation')" class="err" />

            <button class="cta" type="submit" style="margin-top:12px">
                <span>Supprimer définitivement</span>
            </button>
        </form>
    </details>

    <style>
        .data-actions { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin: 12px 0 18px; }
        .delete-box { border-top: 1px solid var(--line); padding-top: 14px; }
        .delete-box summary { cursor: pointer; color: var(--err); font-weight: 600; min-height: 32px; }
    </style>
</section>
