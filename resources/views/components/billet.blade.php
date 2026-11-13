@props(['billet', 'reservation' => null])

@php
    $reservation ??= $billet->reservation;
    $evt = $billet->depart?->evenement ?? $billet->typeBillet?->evenement;
    $arret = $billet->depart ? $billet->depart->tarifs->firstWhere('zone_id', $reservation?->zone_id) : null;
    $statut = $billet->statut;
@endphp

<article {{ $attributes->merge(['class' => 'billet-card']) }}>
    <div class="ticket">
        <div class="top">
            <div class="tag">{{ $billet->depart ? 'CotaTrans · Transport' : 'CotaTrans · Entrée' }}</div>
            <div class="zone">{{ $evt?->titre ?? 'Billet' }}</div>
            <div class="who">{{ $evt?->debut_at?->locale('fr')->translatedFormat('l j F · H\hi') }} · {{ $evt?->lieu }}</div>
        </div>
        <div class="perf"></div>
        <div class="bot billet-body">
            <div class="qr-box">
                <div class="qr-svg" @if ($statut !== \App\Enums\BilletStatus::Valide) style="opacity:.25" @endif>{!! \App\Support\QrCode::svg($billet->numero, 220) !!}</div>
                <div class="mono num">{{ $billet->numero }}</div>
                @if ($statut !== \App\Enums\BilletStatus::Valide)
                    <span class="stamp">{{ $statut->getLabel() }}</span>
                @endif
            </div>
            <dl class="infos">
                <div><dt>Passager</dt><dd>{{ $billet->nom_passager ?: 'Passager' }}</dd></div>
                @if ($billet->depart)
                    <div><dt>Montée</dt><dd>{{ $reservation?->zone?->nom ?? '—' }}@if ($arret?->heure()) · {{ $arret->heure() }}@endif</dd></div>
                    @if ($arret?->point_rdv)
                        <div><dt>Point de RDV</dt><dd>{{ $arret->point_rdv }}</dd></div>
                    @endif
                    <div><dt>Départ</dt><dd>{{ $billet->depart->depart_at->locale('fr')->translatedFormat('D j M · H\hi') }}</dd></div>
                    @if ($billet->siege)
                        <div><dt>Siège</dt><dd>{{ $billet->siege }}</dd></div>
                    @endif
                @endif
                @if ($billet->typeBillet)
                    <div><dt>Catégorie</dt><dd>{{ $billet->typeBillet->nom }}</dd></div>
                @endif
            </dl>
        </div>
    </div>
</article>
