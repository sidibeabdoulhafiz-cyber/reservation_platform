<div class="controle narrow">
    <header class="page-head">
        <div>
            <p class="kicker" style="margin:0 0 4px">Agent de bord</p>
            <h1>Contrôle des billets</h1>
        </div>
    </header>

    <div class="sens" role="radiogroup" aria-label="Sens du contrôle">
        <button type="button" role="radio" aria-checked="{{ $sens === 'aller' ? 'true' : 'false' }}" class="{{ $sens === 'aller' ? 'on' : '' }}" wire:click="$set('sens', 'aller')">
            <x-ico name="arrow-right" size="20" /> <span><b>Aller</b><small>Montée vers l’événement</small></span>
        </button>
        <button type="button" role="radio" aria-checked="{{ $sens === 'retour' ? 'true' : 'false' }}" class="{{ $sens === 'retour' ? 'on retour' : '' }}" wire:click="$set('sens', 'retour')">
            <x-ico name="arrow-left" size="20" /> <span><b>Retour</b><small>Montée après l’événement</small></span>
        </button>
    </div>

    @if ($statut)
        <div class="resultat resultat-{{ $statut }}" role="status" aria-live="assertive">
            <x-ico name="{{ $statut === 'valide' ? 'check-circle' : 'alert' }}" size="30" />
            <div>
                <strong class="res-msg">{{ $message }}</strong>
                @if ($detail)
                    <div class="res-pax">{{ $detail['passager'] ?: 'Passager' }}</div>
                    <div class="res-meta">
                        <span class="mono">{{ $detail['numero'] }}</span>
                        @if ($detail['arret']) · {{ $detail['arret'] }} @endif
                        @if ($detail['siege']) · siège {{ $detail['siege'] }} @endif
                        @if ($detail['evenement']) · {{ $detail['evenement'] }} @endif
                    </div>
                    @if (($detail['sens'] ?? '') !== 'entree')
                        <div class="res-legs">
                            <span @class(['done' => $detail['aller']])>Aller {{ $detail['aller'] ? '✓ '.$detail['aller'] : '—' }}</span>
                            <span @class(['done' => $detail['retour']])>Retour {{ $detail['retour'] ? '✓ '.$detail['retour'] : '—' }}</span>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    @endif

    <div id="lecteur" class="lecteur" aria-label="Caméra" wire:ignore></div>
    <p class="hint" id="camera-aide" wire:ignore>Vise le QR code du billet. La caméra fonctionne sur localhost ou en HTTPS ; sinon, saisis le numéro.</p>

    <form class="panel scan-form" wire:submit="verifier">
        <label class="field" style="margin:0">
            Saisie manuelle du numéro
            <span class="scan-row">
                <input type="text" wire:model="numero" class="scan-input" autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="TR-XXXX-XXXX">
                <button class="btn" type="submit">Vérifier</button>
            </span>
        </label>
        @error('numero') <span class="err">{{ $message }}</span> @enderror
    </form>

    <style>
        .sens { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px; }
        .sens button { display: flex; align-items: center; gap: 10px; padding: 14px; border-radius: var(--r); border: 2px solid var(--line); background: #fff; cursor: pointer; text-align: left; font: inherit; color: var(--fg); min-height: 72px; }
        .sens button small { display: block; color: var(--muted); font-size: 13px; }
        .sens button b { font-size: 1.1rem; }
        .sens button.on { border-color: var(--brand); background: var(--brand); color: #fff; }
        .sens button.on small { color: #c9d8ea; }
        .sens button.on.retour { border-color: var(--accent); background: var(--accent); }
        .sens button.on.retour small { color: #fff3ea; }
        .resultat { margin: 0 0 16px; }
        .res-msg { font-size: 1.15rem; display: block; }
        .res-pax { font: 700 1.3rem var(--display); color: var(--fg); margin-top: 6px; }
        .res-meta { color: var(--muted); font-size: 14px; margin-top: 2px; }
        .res-legs { display: flex; gap: 8px; margin-top: 10px; }
        .res-legs span { font: 600 13px var(--body); padding: 4px 10px; border-radius: 99px; background: rgba(15,27,45,.06); color: var(--muted); }
        .res-legs span.done { background: var(--ok); color: #fff; }
        .scan-row { display: flex; gap: 8px; }
        .scan-row .btn { flex: none; }
    </style>
</div>

@script
<script>
    const demarrer = () => {
        if (!window.Html5Qrcode) return;
        const zone = document.getElementById('lecteur');
        if (!zone || zone.dataset.ready) return;
        zone.dataset.ready = '1';
        const scanner = new Html5Qrcode('lecteur');
        Html5Qrcode.getCameras().then((cameras) => {
            const id = cameras.at(-1)?.id;
            if (!id) {
                const aide = document.getElementById('camera-aide');
                if (aide) aide.textContent = 'Aucune caméra détectée. Saisissez le numéro du billet.';
                return;
            }
            let dernier = '', quand = 0;
            scanner.start(id, { fps: 8, qrbox: 220 }, (texte) => {
                // Évite de valider dix fois le même QR pendant qu'il reste devant la caméra.
                if (texte === dernier && Date.now() - quand < 4000) return;
                dernier = texte; quand = Date.now();
                if (navigator.vibrate) navigator.vibrate(60);
                $wire.set('numero', texte, false);
                $wire.verifier();
            });
        }).catch(() => {
            const aide = document.getElementById('camera-aide');
            if (aide) aide.textContent = window.isSecureContext
                ? 'Caméra refusée. Autorisez-la ou saisissez le numéro.'
                : 'Caméra indisponible hors localhost sans HTTPS. Saisissez le numéro.';
        });
    };
    if (!document.getElementById('html5-qrcode-src')) {
        const s = document.createElement('script');
        s.id = 'html5-qrcode-src';
        s.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';
        s.onload = demarrer;
        document.head.appendChild(s);
    } else {
        demarrer();
    }
</script>
@endscript
