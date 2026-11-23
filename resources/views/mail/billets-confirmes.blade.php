<x-mail::message>
# C’est confirmé, bon voyage !

Bonjour {{ \Illuminate\Support\Str::of($r->user->name)->before(' ') }}, votre paiement est validé. Voici {{ $r->billets->count() > 1 ? 'vos '.$r->billets->count().' billets' : 'votre billet' }} pour **{{ $evt?->titre }}**.

<x-mail::panel>
**Événement :** {{ $evt?->titre }} · {{ $evt?->debut_at?->locale('fr')->translatedFormat('l j F à H\hi') }}<br>
**Lieu :** {{ $evt?->lieu }}, {{ $evt?->ville }}<br>
@if ($r->depart)
**Départ du car :** {{ $r->depart->depart_at->locale('fr')->translatedFormat('l j F à H\hi') }}<br>
**Votre arrêt :** {{ $r->zone?->nom }}{{ $arret?->heure() ? ' · passage vers '.$arret->heure() : '' }}{{ $arret?->point_rdv ? ' · '.$arret->point_rdv : '' }}<br>
@endif
**Réservation :** {{ $r->ref }}
</x-mail::panel>

<x-mail::table>
| Billet | Passager | Siège |
|:-------|:---------|:-----:|
@foreach ($r->billets as $b)
| {{ $b->numero }} | {{ $b->nom_passager ?: 'Passager' }} | {{ $b->siege ?: '—' }} |
@endforeach
</x-mail::table>

Les QR codes sont en **pièces jointes**. Présentez-les à l’agent de bord au moment de monter, à l’écran ou imprimés. Un QR code par passager, contrôlé à l’aller et au retour.

<x-mail::button :url="route('reservations.show', $r)">
Voir mes billets
</x-mail::button>

@if ($evt?->lien_whatsapp)
**Rejoignez la communauté (facultatif) :** infos de dernière minute et prochains événements sur notre [groupe WhatsApp]({{ $evt->lien_whatsapp }}).
@endif

Merci d’être au point de rendez-vous 10 minutes avant le passage du car.

Bon événement,<br>
L’équipe CotaTrans
</x-mail::message>
