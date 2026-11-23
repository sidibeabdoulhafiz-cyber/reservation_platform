<x-mail::message>
{!! nl2br(e($texte)) !!}

<x-mail::panel>
**Votre message du {{ $m->created_at->format('d/m/Y') }}**<br>
{{ \Illuminate\Support\Str::limit($m->message ?: $m->objet(), 300) }}
</x-mail::panel>

Pour nous répondre, il suffit de répondre à cet e-mail.

L’équipe CotaTrans
</x-mail::message>
