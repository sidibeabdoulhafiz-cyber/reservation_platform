<x-mail::message>
# Merci {{ \Illuminate\Support\Str::of($m->nom)->before(' ') }} !

@if ($m->estOrganisateur())
Nous avons bien reçu ta demande pour **{{ $m->evenement }}**. Notre équipe l’étudie et revient vers toi rapidement avec une proposition de transport : lignes, arrêts, capacité et tarif.

Une fois l’accord trouvé, tu recevras le lien de réservation à partager avec ton public.
@else
Nous avons bien reçu ton message et te répondons au plus vite, en général sous 24 h ouvrées.
@endif

<x-mail::panel>
{{ \Illuminate\Support\Str::limit($m->message ?: ($m->evenement ?? ''), 400) }}
</x-mail::panel>

À très vite,<br>
L’équipe CotaTrans
</x-mail::message>
