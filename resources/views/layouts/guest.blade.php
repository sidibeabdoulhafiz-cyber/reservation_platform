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
        .auth-head { margin-bottom: 18px; }
        .auth-head p { margin: 4px 0 0; }
        .auth-form { display: grid; gap: 6px; }
        .auth-form > label:not(.check), .label-row label { font-weight: 600; font-size: 15px; margin-top: 10px; }
        .auth-form input:not([type=checkbox]) { width: 100%; min-height: 52px; font-size: 16px; }
        .label-row { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; }
        .label-row a { color: var(--brand); font-weight: 600; font-size: 14.5px; }
        .field-hint { margin: 0; font-size: 13.5px; color: var(--muted); }
        .auth-form .check { display: flex; gap: 10px; align-items: center; margin: 12px 0 4px; font-weight: 500; }
        .auth-form .check input { width: 20px; height: 20px; accent-color: var(--accent); }
        .auth-form .cta { margin-top: 12px; min-height: 54px; width: 100%; font-size: 17px; }
        @media (max-width: 600px) {
            .auth-wrap { padding: 0 0 16px; }
            .auth { padding: 22px 18px; border-radius: var(--r); box-shadow: none; border: 0; background: transparent; }
            .auth-logo { display: none; }
            .auth .hero h1 { font-size: 1.6rem; }
        }
    </style>
</x-layouts.site>
