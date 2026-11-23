@php
    $waNum = preg_replace('/\D/', '', (string) \App\Models\Setting::get('whatsapp_numero'));
    $reseaux = collect(\App\Filament\Pages\Settings::RESEAUX)
        ->mapWithKeys(fn ($nom, $cle) => [$nom => \App\Models\Setting::get($cle)])
        ->filter()
        ->reject(fn ($u, $nom) => str_starts_with($nom, 'WhatsApp'));
@endphp
<x-mail::layout>
{{-- En-tête --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
CotaTrans
</x-mail::header>
</x-slot:header>

{{-- Contenu --}}
{!! $slot !!}

{{-- Sous-texte --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Pied de page --}}
<x-slot:footer>
<x-mail::footer>
**CotaTrans** · Transport aller-retour pour vos événements à Abidjan<br>
@if (strlen($waNum) >= 8)
[WhatsApp](https://wa.me/{{ $waNum }}) ·
@endif
[Nous contacter]({{ url('/contact') }}) · [Mes réservations]({{ url('/mes-reservations') }})<br>
@if ($reseaux->isNotEmpty())
@foreach ($reseaux as $nom => $lien)[{{ $nom }}]({{ $lien }}){{ $loop->last ? '' : ' · ' }}@endforeach<br>
@endif
<span style="font-size:11.5px;color:#94a3b8;">Vous recevez cet e-mail suite à une action sur votre compte CotaTrans.</span>
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
