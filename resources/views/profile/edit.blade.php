<x-layouts.site title="Mon profil">
    <div class="medium profil">
        <header class="profil-head">
            <span class="avatar-lg" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
            <div>
                <h1 style="margin:0">{{ $user->name }}</h1>
                <p class="muted" style="margin:2px 0 0">{{ $user->email }}@if ($user->tel) · {{ $user->tel }}@endif</p>
            </div>
        </header>

        <nav class="profil-links" aria-label="Raccourcis">
            <a href="{{ route('reservations.index') }}"><x-ico name="ticket" /> <span>Mes réservations</span> <x-ico name="chevron-right" size="18" /></a>
            <a href="{{ route('home') }}#evenements"><x-ico name="search" /> <span>Trouver un événement</span> <x-ico name="chevron-right" size="18" /></a>
        </nav>

        <div class="profil-grid">
            <div class="panel">@include('profile.partials.update-profile-information-form')</div>
            <div class="panel">@include('profile.partials.update-password-form')</div>
        </div>

        <div class="panel danger-zone">@include('profile.partials.delete-user-form')</div>

        <form method="POST" action="{{ route('logout') }}" class="logout-mobile">
            @csrf
            <button type="submit" class="btn btn-ghost btn-block"><x-ico name="logout" size="18" /> Se déconnecter</button>
        </form>
    </div>

    <style>
        .profil-head { display: flex; align-items: center; gap: 16px; margin-bottom: 20px; }
        .avatar-lg { width: 64px; height: 64px; border-radius: 50%; background: var(--brand); color: #fff; display: grid; place-items: center; font: 700 1.6rem var(--display); flex: none; border: 3px solid var(--accent); }
        .profil-links { display: grid; gap: 0; background: #fff; border: 1px solid var(--line); border-radius: var(--r); margin-bottom: 18px; overflow: hidden; }
        .profil-links a { display: flex; align-items: center; gap: 12px; padding: 15px 16px; text-decoration: none; font-weight: 500; min-height: 54px; }
        .profil-links a + a { border-top: 1px solid var(--line); }
        .profil-links a:hover { background: var(--soft); }
        .profil-links a span { flex: 1; }
        .profil-links .ico:first-child { color: var(--brand); }
        .profil-links .ico:last-child { color: #9aa5b4; }
        .profil-grid { display: grid; gap: 18px; margin-bottom: 18px; }
        @media (min-width: 860px) { .profil-grid { grid-template-columns: 1fr 1fr; align-items: start; } }
        .profil h2 { font-size: 1.2rem; color: var(--brand); margin-bottom: 4px; }
        .profil .cta { justify-content: center; }
        .danger-zone h2 { color: var(--brand); }
        .danger-zone .cta { background: var(--err) !important; max-width: 320px; }
        .logout-mobile { display: none; margin-top: 18px; }
        @media (max-width: 860px) { .logout-mobile { display: block; } }
    </style>
</x-layouts.site>
