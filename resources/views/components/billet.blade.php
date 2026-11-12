@props(['billet'])

<article class="ticket-card">
    <div class="ticket">
        <div class="top">
            <div class="tag">{{ $billet->numero }}</div>
            <div class="zone">{{ $billet->depart?->evenement?->titre ?? $billet->typeBillet?->evenement?->titre ?? 'Billet' }}</div>
            <div class="who">
                {{ $billet->nom_passager ?: 'Passager' }}
                @if ($billet->siege) · siège {{ $billet->siege }} @endif
                @if ($billet->typeBillet) · {{ $billet->typeBillet->nom }} @endif
            </div>
        </div>
        <div class="perf"></div>
        <div class="bot qr">
            <div class="qr-svg">{!! \App\Support\QrCode::svg($billet->numero) !!}</div>
            <div>
                <div class="mono">{{ $billet->numero }}</div>
                <div class="hint">Présentez ce code à l’entrée.</div>
            </div>
        </div>
    </div>
</article>
