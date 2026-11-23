<x-mail::message>
# Merci {{ \Illuminate\Support\Str::of($m->nom)->before(' ') }}, c’est bien reçu

@if ($m->estOrganisateur())
Nous avons bien reçu votre demande pour **{{ $m->evenement }}**. Notre équipe l’étudie et revient vers vous rapidement avec une proposition de transport : lignes, arrêts, capacité et tarif.

Une fois l’accord trouvé, vous recevrez le lien de réservation à partager avec votre public.
@else
Nous avons bien reçu votre message et vous répondons au plus vite, en général sous 24 h ouvrées.
@endif

<x-mail::panel>
**Votre message**<br>
{{ \Illuminate\Support\Str::limit($m->message ?: ($m->evenement ?? ''), 400) }}
</x-mail::panel>

À très bientôt,<br>
L’équipe CotaTrans
</x-mail::message>
