<x-layouts.site title="Simulation Wave (local)">
    <header class="hero">
        <h1>Simulation Wave</h1>
        <p>Page de test locale, sans HTTPS. En production, ce bouton ouvre le vrai lien Wave.</p>
    </header>

    <section class="panel">
        <p class="hint">Montant à payer</p>
        <p class="mono" style="font-size:1.6rem;margin:0 0 16px">{{ $montant > 0 ? number_format($montant, 0, ',', ' ').' FCFA' : 'Montant non indiqué' }}</p>

        <p class="hint">Identifiant de transaction (à copier)</p>
        <p class="mono" style="font-size:1.2rem;margin:0 0 16px" id="tx">{{ $tx }}</p>

        <button class="cta" type="button" onclick="navigator.clipboard.writeText(document.getElementById('tx').innerText)">
            <span>Copier l’identifiant</span>
        </button>

        <p class="hint" style="margin-top:16px">Faites une capture de cet écran, revenez à la réservation, collez l’identifiant et envoyez le reçu.</p>
        <p><a href="{{ route('home') }}">Retour à la réservation</a></p>
    </section>
</x-layouts.site>
