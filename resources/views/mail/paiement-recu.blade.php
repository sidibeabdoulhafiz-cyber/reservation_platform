@php $evt = $r->evenement(); @endphp
<x-mail::message>
# Reçu bien reçu ✓

Merci {{ \Illuminate\Support\Str::of($r->user->name)->before(' ') }} ! Nous avons bien reçu la preuve de paiement pour **{{ $evt?->titre }}** (réservation {{ $r->ref }}).

Notre équipe la vérifie en ce moment. Dès qu’elle est validée, tu reçois tes billets QR code par e-mail. Ils sont aussi disponibles dans « Mes billets ».

<x-mail::button :url="route('reservations.show', $r)">
Suivre ma réservation
</x-mail::button>

L’équipe CotaTrans
</x-mail::message>
