<div class="controle">
    <header class="hero">
        <h1>Contrôle à l’entrée</h1>
        <p>Scannez le QR code ou saisissez le numéro du billet.</p>
    </header>

    <div id="lecteur" class="lecteur" aria-label="Caméra"></div>
    <p class="hint" id="camera-aide">La caméra fonctionne sur http://localhost. Sur une autre adresse HTTP, saisissez le numéro à la main.</p>

    <form class="panel" wire:submit="verifier">
        <label>
            Numéro du billet
            <input type="text" wire:model="numero" class="scan-input" autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="TR-XXXX-XXXX">
        </label>
        <button class="cta" type="submit">Vérifier le billet</button>
    </form>

    @if ($statut)
        <div class="resultat resultat-{{ $statut }}" role="status">
            <strong>{{ $message }}</strong>
            @if ($detail)
                <div class="hint">{{ $detail['numero'] }}
                    @if ($detail['passager']) · {{ $detail['passager'] }} @endif
                    @if ($detail['siege']) · siège {{ $detail['siege'] }} @endif
                    @if ($detail['evenement']) · {{ $detail['evenement'] }} @endif
                </div>
            @endif
        </div>
    @endif
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
            scanner.start(id, { fps: 8, qrbox: 220 }, (texte) => {
                $wire.set('numero', texte);
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
