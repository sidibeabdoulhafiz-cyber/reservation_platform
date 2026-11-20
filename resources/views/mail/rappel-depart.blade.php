<x-mail::message>
@if ($moment === 'veille')
# C’est demain !
@else
# Ton car part bientôt
@endif

Bonjour {{ \Illuminate\Support\Str::of($r->user->name)->before(' ') }}, voici les informations de ton départ pour **{{ $evt?->titre }}**.

<x-mail::panel>
**Aller :** {{ $r->depart->depart_at->locale('fr')->translatedFormat('l j F') }} · passage vers **{{ $arret?->heure() ?? $r->depart->depart_at->format('H\hi') }}**<br>
**Ton arrêt :** {{ $r->zone?->nom }}{{ $arret?->point_rdv ? ' · '.$arret->point_rdv : '' }}<br>
@if ($r->depart->retour_at)
**Retour :** départ de {{ $evt?->lieu }} vers {{ $r->depart->retour_at->format('H\hi') }}<br>
@endif
**Passagers :** {{ $r->billets->pluck('nom_passager')->filter()->implode(', ') ?: $r->quantite }}
</x-mail::panel>

Sois au point de rendez-vous **10 minutes avant** l’heure de passage, avec ton QR code (à l’écran ou imprimé). Un QR par passager : il est contrôlé à l’aller et au retour.

<x-mail::button :url="route('reservations.show', $r)">
Afficher mes billets
</x-mail::button>

Bon événement,<br>
L’équipe CotaTrans
</x-mail::message>
