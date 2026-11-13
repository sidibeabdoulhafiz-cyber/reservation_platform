@props(['evenement', 'badge' => true, 'eager' => false])

<div {{ $attributes->merge(['class' => 'cover']) }} style="background: {{ $evenement->couleurSecours() }}">
    @if ($url = $evenement->imageUrl())
        <img src="{{ $url }}" alt="Affiche : {{ $evenement->titre }}" loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async">
    @else
        <div class="fallback" aria-hidden="true">{{ $evenement->titre }}</div>
    @endif

    @if ($badge)
        <div class="date-badge" aria-label="{{ $evenement->debut_at->locale('fr')->translatedFormat('l j F Y') }}">
            <b>{{ $evenement->debut_at->format('d') }}</b>
            <small>{{ $evenement->debut_at->locale('fr')->translatedFormat('M') }}</small>
        </div>
    @endif
</div>
