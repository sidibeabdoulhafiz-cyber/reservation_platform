<x-guest-layout title="Vérification">
    <header class="hero">
        <h1>{{ $nouveau ? 'Confirmez votre e-mail' : 'Vérification de sécurité' }}</h1>
        <p>Nous avons envoyé un code à 6 chiffres à <b>{{ $email }}</b>. Saisissez-le ci-dessous.</p>
    </header>

    @if (session('status'))
        <div class="alert alert-ok" role="status"><x-ico name="check-circle" /> {{ session('status') }}</div>
    @endif
    @if ($erreurEnvoi)
        <div class="alert alert-warn" role="alert"><x-ico name="info" /> L’e-mail n’a pas pu partir. Touchez « Renvoyer le code » dans un instant.</div>
    @endif

    <form method="POST" action="{{ route('code.store') }}" x-data="{ code: '' }">
        @csrf
        <label for="code" class="sr">Code reçu par e-mail</label>
        <input id="code" name="code" class="code-input" type="text" inputmode="numeric" pattern="[0-9 ]*" maxlength="7"
               autocomplete="one-time-code" autofocus required placeholder="••••••" x-model="code"
               @input="code = code.replace(/\D/g, '').slice(0, 6)">
        <x-input-error :messages="$errors->get('code')" class="err" />

        <label class="check">
            <input type="checkbox" name="confiance" value="1" checked>
            <span>Ne plus me demander de code sur cet appareil pendant {{ config('cotatrans.appareil_confiance_jours') }} jours</span>
        </label>

        <button class="cta" type="submit" :disabled="code.length !== 6">Valider le code</button>
    </form>

    <div class="code-help" x-data="{ s: {{ (int) $attente }}, init() { const t = setInterval(() => { if (this.s > 0) this.s--; else clearInterval(t); }, 1000); } }">
        <p class="hint">Rien reçu ? Regardez dans vos spams ou l’onglet « Promotions ».</p>
        <form method="POST" action="{{ route('code.renvoyer') }}">
            @csrf
            <button type="submit" class="linklike" :disabled="s > 0">
                <span x-show="s > 0">Renvoyer le code dans <span x-text="s"></span> s</span>
                <span x-show="s === 0" x-cloak>Renvoyer le code</span>
            </button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="linklike muted">Changer de compte</button>
        </form>
    </div>

    <style>
        .hero { text-align: center; }
        .code-input { font: 700 30px/1 ui-monospace, SFMono-Regular, Menlo, monospace; letter-spacing: .45em; text-align: center; padding: 14px 10px 14px calc(10px + .45em); width: 100%; }
        .cta:disabled { opacity: .5; cursor: not-allowed; }
        .check { display: flex; gap: 10px; align-items: flex-start; margin: 14px 0 4px; font-weight: 500; font-size: 14.5px; }
        .check input { width: 20px; height: 20px; margin-top: 1px; accent-color: var(--accent, #FB7212); flex: none; }
        .code-help { margin-top: 18px; text-align: center; display: grid; gap: 6px; justify-items: center; }
        .code-help .muted { color: var(--muted, #64748b); font-size: 14px; }
        .linklike:disabled { opacity: .6; cursor: default; text-decoration: none; }
    </style>
</x-guest-layout>
