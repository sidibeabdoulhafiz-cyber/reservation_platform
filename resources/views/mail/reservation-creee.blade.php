@php $evt = $r->evenement(); $arret = $r->depart?->tarifs->firstWhere('zone_id', $r->zone_id); @endphp
<x-mail::message>
# Plus qu’une étape, {{ \Illuminate\Support\Str::of($r->user->name)->before(' ') }} !

Votre réservation pour **{{ $evt?->titre }}** est enregistrée. Vos places sont bloquées{{ $r->expire_at ? ' jusqu’à '.$r->expire_at->format('H\hi') : '' }} : payez maintenant pour les confirmer.

<x-mail::panel>
**Réservation :** {{ $r->ref }}<br>
@if ($r->zone)
**Montée :** {{ $r->zone->nom }}{{ $arret?->heure() ? ' · vers '.$arret->heure() : '' }}<br>
@endif
**Places :** {{ $r->quantite }}<br>
**Total à payer :** {{ number_format($r->montant, 0, ',', ' ') }} FCFA
</x-mail::panel>

<x-mail::button :url="route('reservations.show', $r)">
Payer et envoyer mon reçu
</x-mail::button>

Passé ce délai, les places sont automatiquement libérées.

À très vite à bord,<br>
L’équipe CotaTrans
</x-mail::message>
