<x-layouts.site :title="$title ?? config('app.name')">
    @isset($header)
        <header class="hero">{{ $header }}</header>
    @endisset

    <section class="panel">
        {{ $slot }}
    </section>
</x-layouts.site>
