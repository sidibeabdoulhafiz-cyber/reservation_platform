<x-layouts.site :title="$title ?? 'Connexion'">
    <section class="auth-wrap">
        <div class="panel auth">
            <img src="{{ asset('images/logo-icone.svg') }}" alt="" class="auth-logo" width="44" height="52">
            {{ $slot }}
        </div>
    </section>
    <style>
        .auth-wrap { padding: 12px 0 24px; }
        .auth { padding: 30px 28px; box-shadow: var(--shadow); }
        .auth-logo { height: 52px; width: auto; margin-bottom: 14px; }
        .auth .hero h1 { font-size: 1.7rem; }
        .auth .cta { margin-top: 6px; justify-content: center; }
        .auth-alt { margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--line); text-align: center; color: var(--muted); }
        .auth-alt a { color: var(--brand); font-weight: 600; }
        @media (max-width: 600px) { .auth { padding: 22px 18px; border-radius: var(--r); } }
    </style>
</x-layouts.site>
