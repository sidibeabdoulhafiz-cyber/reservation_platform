<x-layouts.site :title="$title ?? 'Mon compte'">
    @isset($header)
        <header class="hero">{{ $header }}</header>
    @endisset

    <section class="panel">
        {{ $slot }}
    </section>
</x-layouts.site>
