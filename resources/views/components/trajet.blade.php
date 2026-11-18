@props(['reservation', 'compact' => false])
@php
    $r = $reservation;
    $evt = $r->evenement();
    $depart = $r->depart;
    $arret = $depart?->tarifs?->firstWhere('zone_id', $r->zone_id);
@endphp

{{-- Informations aller / retour d'une réservation de transport --}}
@if ($depart)
    <ol {{ $attributes->merge(['class' => 'trajet'.($compact ? ' compact' : '')]) }}>
        <li>
            <span class="t-tag">Aller</span>
            <div class="t-body">
                <b>{{ $depart->depart_at->locale('fr')->translatedFormat('l j F') }} · {{ $arret?->heure() ?? $depart->depart_at->format('H\hi') }}</b>
                <span><x-ico name="pin" size="15" /> {{ $r->zone?->nom }}@if ($arret?->point_rdv) · {{ $arret->point_rdv }}@endif</span>
                @unless ($compact)
                    <span class="t-to"><x-ico name="flag" size="15" /> {{ $evt?->lieu }}</span>
                @endunless
            </div>
        </li>
        <li>
            <span class="t-tag ret">Retour</span>
            <div class="t-body">
                <b>{{ $depart->retour_at ? $depart->retour_at->locale('fr')->translatedFormat('l j F · H\hi') : 'Après l’événement' }}</b>
                <span><x-ico name="pin" size="15" /> {{ $evt?->lieu }}</span>
                @unless ($compact)
                    <span class="t-to"><x-ico name="flag" size="15" /> {{ $r->zone?->nom }}</span>
                @endunless
            </div>
        </li>
    </ol>
@endif
