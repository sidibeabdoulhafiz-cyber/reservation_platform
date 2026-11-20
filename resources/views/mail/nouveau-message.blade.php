<x-mail::message>
# {{ $m->estOrganisateur() ? 'Nouvelle demande organisateur' : 'Nouveau message de contact' }}

<x-mail::panel>
**De :** {{ $m->nom }}{{ $m->organisation ? ' ('.$m->organisation.')' : '' }}<br>
**E-mail :** {{ $m->email }}<br>
@if ($m->tel)
**Téléphone :** {{ $m->tel }}<br>
@endif
@if ($m->estOrganisateur())
**Événement :** {{ $m->evenement }}<br>
**Date :** {{ $m->date_evenement?->locale('fr')->translatedFormat('l j F Y') ?? 'non précisée' }}<br>
**Lieu :** {{ $m->lieu ?: 'non précisé' }}<br>
**Participants attendus :** {{ $m->participants ? number_format($m->participants, 0, ',', ' ') : 'non précisé' }}<br>
@else
**Sujet :** {{ $m->sujet ?: '—' }}<br>
@endif
</x-mail::panel>

@if ($m->message)
{{ $m->message }}
@endif

<x-mail::button :url="url('/admin/messages')">
Voir dans l’administration
</x-mail::button>

Réponds directement à cet e-mail pour écrire à {{ $m->nom }}.
</x-mail::message>
