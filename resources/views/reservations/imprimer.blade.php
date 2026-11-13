<x-layouts.site :title="'Billets '.$reservation->ref">
    <div class="narrow">
        <div class="noprint" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px">
            <button type="button" class="btn" onclick="window.print()">🖨️ Imprimer ou enregistrer en PDF</button>
            <a class="btn btn-ghost" href="{{ route('reservations.show', $reservation) }}">← Retour</a>
        </div>
        <p class="hint noprint" style="margin:-6px 0 18px">Sur téléphone : « Imprimer » puis « Enregistrer au format PDF ».</p>

        <div style="display:grid;gap:18px">
            @foreach ($reservation->billets as $billet)
                <x-billet :billet="$billet" :reservation="$reservation" />
            @endforeach
        </div>

        <p class="hint" style="margin-top:18px;text-align:center">Réservation {{ $reservation->ref }} · CotaTrans</p>
    </div>
</x-layouts.site>
