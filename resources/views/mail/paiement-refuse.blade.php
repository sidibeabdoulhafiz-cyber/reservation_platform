@php $evt = $r->evenement(); @endphp
<x-mail::message>
# Ton reçu n’a pas pu être validé

Bonjour {{ \Illuminate\Support\Str::of($r->user->name)->before(' ') }}, nous n’avons pas pu valider le reçu envoyé pour **{{ $evt?->titre }}** (réservation {{ $r->ref }}).

<x-mail::panel>
**Motif :** {{ $motif }}
</x-mail::panel>

Pas d’inquiétude : tes places restent bloquées{{ $r->expire_at ? ' jusqu’à '.$r->expire_at->format('H\hi') : '' }}. Envoie un reçu valide pour les confirmer.

<x-mail::button :url="route('reservations.show', $r)">
Envoyer un nouveau reçu
</x-mail::button>

L’équipe CotaTrans
</x-mail::message>
