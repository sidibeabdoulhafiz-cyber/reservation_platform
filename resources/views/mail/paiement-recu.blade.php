@php $evt = $r->evenement(); @endphp
<x-mail::message>
# Preuve de paiement reçue

Merci {{ \Illuminate\Support\Str::of($r->user->name)->before(' ') }} ! Nous avons bien reçu votre preuve de paiement pour **{{ $evt?->titre }}** (réservation {{ $r->ref }}).

Notre équipe la vérifie. Dès qu’elle est validée, vous recevez vos billets QR code par e-mail. Ils sont aussi disponibles dans « Mes réservations ».

<x-mail::button :url="route('reservations.show', $r)">
Suivre ma réservation
</x-mail::button>

L’équipe CotaTrans
</x-mail::message>
