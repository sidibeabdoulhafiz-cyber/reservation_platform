<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f1a2b">
    <title>{{ isset($title) ? $title.' · CotaTrans' : 'CotaTrans · Transport aller-retour pour vos événements' }}</title>
    <meta name="description" content="Réservez votre place en car pour vos concerts, festivals et matchs. Paiement mobile, billet QR code, ramassage près de chez vous.">
    @isset($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
    @endisset
    @livewireStyles
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f4f1ea;
            --surface: #fff;
            --fg: #0f1a2b;
            --muted: #5d6676;
            --line: #e2ddd2;
            --soft: #faf8f3;
            --accent: #ff5a1f;
            --accent-dark: #d9430c;
            --accent-soft: #fff1ea;
            --accent-fg: #fff;
            --ok: #0e7a55;
            --ok-soft: #e6f5ee;
            --err: #b42318;
            --err-soft: #fdecec;
            --warn: #a15c07;
            --warn-soft: #fff5e3;
            --ink: #0f1a2b;
            --ink-fg: #f3f5f9;
            --ink-muted: #9aa6bb;
            --radius: 18px;
            --shadow: 0 1px 2px rgba(15, 26, 43, .06), 0 8px 24px rgba(15, 26, 43, .06);
            --display: 'Bricolage Grotesque', system-ui, sans-serif;
            --body: 'DM Sans', system-ui, sans-serif;
            --mono: 'DM Mono', ui-monospace, monospace;
        }
        *, *::before, *::after { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }
        body { margin: 0; color: var(--fg); font: 16px/1.5 var(--body); background: var(--bg); min-height: 100vh; display: flex; flex-direction: column; }
        img { max-width: 100%; display: block; }
        a { color: inherit; }
        a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible, label:focus-within { outline: 2px solid var(--accent); outline-offset: 2px; }
        [x-cloak] { display: none !important; }
        .sr { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); }

        /* ---------- En-tête ---------- */
        .site-top { background: var(--ink); color: var(--ink-fg); position: sticky; top: 0; z-index: 30; }
        .nav { display: flex; justify-content: space-between; align-items: center; gap: 8px; padding: 10px 16px; max-width: 1120px; margin: 0 auto; }
        .brand { display: inline-flex; align-items: center; gap: 10px; text-decoration: none; color: inherit; }
        .brand-mark { width: 34px; height: 34px; border-radius: 10px; background: var(--accent); display: grid; place-items: center; }
        .brand-mark svg { width: 20px; height: 20px; }
        .brand-name { font: 800 1.2rem var(--display); letter-spacing: -.03em; }
        .brand-name i { font-style: normal; color: var(--accent); }
        .links { display: flex; gap: 2px; align-items: center; }
        .links form { margin: 0; }
        .links a, .linkbtn {
            font: 600 14px var(--body); color: var(--ink-fg); text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 12px; min-height: 44px; border-radius: 10px; background: none; border: 0; cursor: pointer;
        }
        .links a:hover, .linkbtn:hover { background: rgba(255,255,255,.08); }
        .links a.on { background: rgba(255,255,255,.12); }
        .links .pill { background: var(--accent); color: #fff; }
        .links .pill:hover { background: var(--accent-dark); }
        .menu-toggle { display: none; }
        @media (max-width: 720px) {
            .menu-toggle { display: inline-flex; }
            .links { display: none; position: absolute; top: 100%; left: 0; right: 0; background: var(--ink); flex-direction: column; align-items: stretch; padding: 8px 12px 14px; border-top: 1px solid rgba(255,255,255,.08); }
            .links.open { display: flex; }
            .links a, .linkbtn { width: 100%; justify-content: flex-start; }
        }

        /* ---------- Structure ---------- */
        main { flex: 1; }
        .wrap { max-width: 1120px; margin: 0 auto; padding: 24px 16px 120px; }
        .narrow { max-width: 560px; margin: 0 auto; }
        .mono { font-family: var(--mono); font-size: 13px; }
        .hint { color: var(--muted); font-size: 14px; margin: 6px 0 0; }
        h1 { font: 800 clamp(1.9rem, 6vw, 3rem)/1.04 var(--display); margin: 0 0 10px; letter-spacing: -.035em; }
        h2 { font: 800 clamp(1.3rem, 3.5vw, 1.7rem)/1.15 var(--display); margin: 0 0 12px; letter-spacing: -.02em; }
        h3 { font: 700 1.1rem var(--display); margin: 0 0 6px; }
        .hero { margin-bottom: 20px; }
        .hero p { color: var(--muted); margin: 0; max-width: 60ch; }
        .panel { background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius); padding: 20px; box-shadow: var(--shadow); }
        .site-foot { border-top: 1px solid var(--line); color: var(--muted); font-size: 14px; }
        .site-foot .in { max-width: 1120px; margin: 0 auto; padding: 20px 16px calc(20px + env(safe-area-inset-bottom)); display: flex; flex-wrap: wrap; justify-content: space-between; gap: 8px; }

        /* ---------- Boutons ---------- */
        .cta, .btn {
            font: 700 1rem var(--display); border: 0; border-radius: 14px; background: var(--accent); color: var(--accent-fg);
            padding: 12px 18px; min-height: 50px; cursor: pointer; text-decoration: none;
            display: inline-flex; justify-content: center; align-items: center; gap: 10px; transition: background .15s, transform .05s;
        }
        .cta:hover, .btn:hover { background: var(--accent-dark); }
        .cta:active, .btn:active { transform: translateY(1px); }
        .cta { width: 100%; justify-content: space-between; padding: 16px 20px; }
        .cta.center { justify-content: center; }
        .cta[disabled], .btn[disabled] { opacity: .45; cursor: not-allowed; }
        .amt { font-family: var(--mono); font-weight: 500; }
        .btn-ghost { background: var(--surface); color: var(--fg); border: 1.5px solid var(--line); }
        .btn-ghost:hover { background: var(--soft); border-color: var(--fg); }
        .btn-wa { background: #25d366; color: #0b2e1a; }
        .btn-wa:hover { background: #1fb457; }
        .btn-sm { min-height: 40px; padding: 8px 14px; font-size: .9rem; border-radius: 10px; }
        .linklike { background: none; border: 0; padding: 0; color: var(--accent-dark); font: inherit; font-weight: 600; cursor: pointer; text-decoration: underline; text-underline-offset: 3px; }

        /* ---------- Formulaires ---------- */
        fieldset { border: 0; padding: 0; margin: 0 0 24px; min-width: 0; }
        legend { font: 700 1.05rem var(--display); margin-bottom: 10px; padding: 0; display: flex; align-items: center; gap: 10px; }
        legend .num { width: 26px; height: 26px; border-radius: 50%; background: var(--ink); color: #fff; display: inline-grid; place-items: center; font: 600 13px var(--body); flex: none; }
        .field { display: grid; gap: 6px; font-weight: 600; font-size: 14px; margin: 0 0 14px; }
        .field input, .field select, .field textarea, .panel input[type=text], .panel input[type=email], .panel input[type=password], .panel input[type=number], .panel input[type=tel] {
            font: 500 16px var(--body); background: var(--soft); border: 1.5px solid var(--line); border-radius: 12px; padding: 12px 14px; width: 100%; min-height: 50px; color: var(--fg);
        }
        .field input:focus, .panel input:focus { border-color: var(--accent); background: #fff; outline: none; box-shadow: 0 0 0 3px var(--accent-soft); }
        .panel form label { display: grid; gap: 6px; font-weight: 600; font-size: 14px; margin: 0 0 14px; }
        .panel form .check { display: flex; flex-direction: row; align-items: center; gap: 8px; font-weight: 500; }
        .panel form .check input { width: auto; min-height: auto; }
        .grid2 { display: grid; gap: 0 12px; grid-template-columns: 1fr; }
        @media (min-width: 560px) { .grid2 { grid-template-columns: 1fr 1fr; } }
        .err, .field .err { color: var(--err); font-size: 13px; font-weight: 500; }

        .stepper { display: inline-flex; align-items: center; border: 1.5px solid var(--line); border-radius: 14px; background: var(--soft); }
        .stepper button { width: 54px; height: 54px; border: 0; background: none; font: 600 26px var(--body); cursor: pointer; color: var(--fg); border-radius: 14px; }
        .stepper button:hover:not([disabled]) { background: var(--accent-soft); color: var(--accent-dark); }
        .stepper button[disabled] { opacity: .3; cursor: not-allowed; }
        .stepper output { font: 600 22px var(--mono); min-width: 52px; text-align: center; }

        /* Options sélectionnables */
        .opts { display: grid; gap: 10px; }
        .opt { position: relative; display: block; }
        .opt input { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; margin: 0; z-index: 1; }
        .opt > span { display: flex; align-items: center; justify-content: space-between; gap: 12px; border: 1.5px solid var(--line); background: var(--soft); border-radius: 14px; padding: 14px 16px; min-height: 64px; transition: border-color .15s, background .15s; }
        .opt b { font-family: var(--display); font-size: 1.05rem; display: block; }
        .opt small { color: var(--muted); display: block; }
        .opt:hover > span { border-color: #c9c2b4; }
        .opt input:checked + span { border-color: var(--accent); background: var(--accent-soft); box-shadow: inset 0 0 0 1px var(--accent); }
        .opt input:checked + span b { color: var(--accent-dark); }
        .opt input:focus-visible + span { outline: 2px solid var(--accent); outline-offset: 2px; }
        .opt input:disabled + span { opacity: .5; }
        .opt .price { font: 600 15px var(--mono); white-space: nowrap; }

        /* Chips (départs) */
        .chips { display: flex; gap: 8px; overflow-x: auto; padding-bottom: 4px; scrollbar-width: thin; }
        .chip { position: relative; flex: none; }
        .chip input { position: absolute; inset: 0; opacity: 0; cursor: pointer; margin: 0; }
        .chip span { display: grid; gap: 2px; padding: 10px 14px; border: 1.5px solid var(--line); border-radius: 14px; background: var(--soft); min-width: 132px; }
        .chip b { font: 700 1rem var(--display); }
        .chip small { color: var(--muted); font-size: 12px; }
        .chip input:checked + span { border-color: var(--accent); background: var(--accent-soft); box-shadow: inset 0 0 0 1px var(--accent); }
        .chip input:focus-visible + span { outline: 2px solid var(--accent); outline-offset: 2px; }
        .chip small.low { color: var(--err); font-weight: 600; }

        /* Itinéraire (arrêts) */
        .route { list-style: none; margin: 0; padding: 0; display: grid; gap: 8px; }
        .stop { position: relative; }
        .stop input { position: absolute; inset: 0; opacity: 0; cursor: pointer; margin: 0; z-index: 1; }
        .stop .body { display: grid; grid-template-columns: 56px 22px 1fr auto; align-items: center; gap: 10px; padding: 12px 14px; border: 1.5px solid var(--line); border-radius: 14px; background: var(--soft); }
        .stop .time { font: 600 14px var(--mono); color: var(--muted); }
        .stop .dot { width: 14px; height: 14px; border-radius: 50%; border: 3px solid var(--fg); background: #fff; justify-self: center; position: relative; }
        .stop.principal .dot { background: var(--accent); border-color: var(--accent); width: 18px; height: 18px; }
        .stop .name b { font: 700 1.02rem var(--display); display: block; }
        .stop .name small { color: var(--muted); display: block; font-size: 13px; }
        .stop .tag-main { display: inline-block; font: 600 11px var(--body); text-transform: uppercase; letter-spacing: .05em; color: var(--accent-dark); background: var(--accent-soft); padding: 2px 8px; border-radius: 99px; margin-left: 6px; vertical-align: middle; }
        .stop input:checked + .body { border-color: var(--accent); background: var(--accent-soft); box-shadow: inset 0 0 0 1px var(--accent); }
        .stop input:checked + .body .dot { border-color: var(--accent); }
        .stop input:focus-visible + .body { outline: 2px solid var(--accent); outline-offset: 2px; }
        .route-end { display: grid; grid-template-columns: 56px 22px 1fr; gap: 10px; align-items: center; padding: 6px 14px; color: var(--muted); font-size: 14px; }
        .route-end .flag { justify-self: center; font-size: 16px; }

        /* ---------- Alertes & badges ---------- */
        .alert { padding: 12px 14px; border-radius: 14px; margin-bottom: 16px; background: var(--surface); border: 1px solid var(--line); display: flex; gap: 10px; align-items: flex-start; }
        .alert-ok { border-color: #a7dcc4; background: var(--ok-soft); color: var(--ok); }
        .alert-warn { border-color: #f3d39b; background: var(--warn-soft); color: var(--warn); }
        .alert-err { border-color: #f4b8b3; background: var(--err-soft); color: var(--err); }
        .alert a { font-weight: 700; }
        .badge { font: 600 12px var(--body); padding: 5px 10px; border-radius: 99px; border: 1px solid var(--line); white-space: nowrap; display: inline-block; }
        .badge.a_payer { color: var(--accent-dark); border-color: #ffc7ad; background: var(--accent-soft); }
        .badge.en_verification { color: var(--warn); border-color: #f3d39b; background: var(--warn-soft); }
        .badge.payee { color: var(--ok); border-color: #a7dcc4; background: var(--ok-soft); }
        .badge.annulee, .badge.expiree { color: var(--muted); }

        /* ---------- Cartes événement ---------- */
        .cards { display: grid; gap: 18px; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); }
        .card { background: var(--surface); border-radius: var(--radius); overflow: hidden; border: 1px solid var(--line); box-shadow: var(--shadow); text-decoration: none; display: flex; flex-direction: column; transition: transform .15s, box-shadow .15s; }
        .card:hover { transform: translateY(-3px); box-shadow: 0 2px 4px rgba(15,26,43,.06), 0 18px 40px rgba(15,26,43,.12); }
        .cover { position: relative; aspect-ratio: 16 / 9; background-size: cover; background-position: center; overflow: hidden; }
        .cover img { width: 100%; height: 100%; object-fit: cover; }
        .cover .fallback { position: absolute; inset: 0; display: grid; place-items: center; color: rgba(255,255,255,.92); font: 800 1.6rem var(--display); letter-spacing: -.03em; padding: 16px; text-align: center; }
        .date-badge { position: absolute; top: 12px; left: 12px; background: #fff; color: var(--fg); border-radius: 12px; padding: 6px 10px; text-align: center; line-height: 1.05; box-shadow: 0 4px 12px rgba(0,0,0,.15); }
        .date-badge b { display: block; font: 800 1.35rem var(--display); }
        .date-badge small { font: 700 11px var(--body); text-transform: uppercase; letter-spacing: .06em; color: var(--accent-dark); }
        .card .content { padding: 16px 18px 18px; display: grid; gap: 6px; flex: 1; }
        .card .meta { color: var(--muted); font-size: 14px; display: flex; gap: 6px; align-items: center; }
        .card .foot { display: flex; justify-content: space-between; align-items: center; margin-top: auto; padding-top: 10px; }
        .from { font-size: 13px; color: var(--muted); }
        .from b { font: 600 1.05rem var(--mono); color: var(--fg); }
        .go { font: 700 14px var(--display); color: var(--accent-dark); }

        /* ---------- Ticket ---------- */
        .ticket { background: var(--ink); color: var(--ink-fg); border-radius: var(--radius); overflow: hidden; box-shadow: 0 16px 40px rgba(15, 26, 43, .25); }
        .ticket .top { padding: 20px 22px 16px; }
        .ticket .tag { font: 500 12px var(--mono); color: #ffb08c; letter-spacing: .08em; text-transform: uppercase; }
        .ticket .zone { font: 800 1.5rem/1.1 var(--display); margin: 8px 0 4px; letter-spacing: -.03em; }
        .ticket .who { color: var(--ink-muted); font-size: 14px; }
        .perf { border-top: 2px dashed #34425a; margin: 0 14px; position: relative; }
        .perf::before, .perf::after { content: ''; position: absolute; top: -10px; width: 20px; height: 20px; background: var(--bg); border-radius: 50%; }
        .perf::before { left: -24px; }
        .perf::after { right: -24px; }
        .ticket .bot { padding: 16px 22px 20px; display: grid; gap: 8px; }
        .line, .total { display: flex; justify-content: space-between; gap: 12px; font-size: 14px; }
        .line { color: var(--ink-muted); }
        .line span:last-child { font-family: var(--mono); color: var(--ink-fg); text-align: right; }
        .total { margin-top: 6px; padding-top: 12px; border-top: 1px solid #34425a; }
        .total span:last-child { font: 500 1.35rem var(--mono); }

        .note { margin: 0 0 12px; }
        .note-ok { color: var(--ok); }
        .barre { height: 6px; background: var(--line); border-radius: 99px; overflow: hidden; }
        .barre span { display: block; height: 100%; background: var(--accent); transition: width .2s; }

        /* Billet QR */
            .billet-card { break-inside: avoid; page-break-inside: avoid; }
            .billet-body { display: grid !important; grid-template-columns: 1fr; gap: 16px !important; }
            @media (min-width: 480px) { .billet-body { grid-template-columns: 170px 1fr; align-items: center; } }
            .qr-box { position: relative; text-align: center; }
            .qr-svg { background: #fff; border-radius: 12px; padding: 8px; max-width: 220px; margin: 0 auto; }
            .qr-svg svg { display: block; width: 100%; height: auto; }
            .qr-box .num { margin-top: 8px; color: var(--ink-fg); letter-spacing: .06em; }
            .stamp { position: absolute; top: 40%; left: 50%; transform: translate(-50%, -50%) rotate(-12deg); border: 3px solid var(--err); color: var(--err); background: #fff; padding: 4px 12px; font: 800 1.1rem var(--display); text-transform: uppercase; border-radius: 6px; }
            .infos { margin: 0; display: grid; gap: 10px; }
            .infos div { display: grid; gap: 1px; }
            .infos dt { color: var(--ink-muted); font-size: 12px; text-transform: uppercase; letter-spacing: .05em; }
            .infos dd { margin: 0; font-weight: 600; }
            @media print { .ticket { box-shadow: none; -webkit-print-color-adjust: exact; print-color-adjust: exact; } }

        /* Barre d'action collante en bas (mobile) */
        .bar { position: sticky; bottom: 0; z-index: 10; padding: 12px 0 calc(12px + env(safe-area-inset-bottom)); background: linear-gradient(transparent, var(--bg) 30%); }
        .bar .cta { min-height: 58px; box-shadow: 0 10px 24px rgba(255, 90, 31, .35); }

        /* Scanner */
        .lecteur { min-height: 240px; background: var(--ink); border-radius: var(--radius); overflow: hidden; margin-bottom: 16px; }
        .scan-input { font: inherit; background: var(--soft); border: 1.5px solid var(--line); border-radius: 12px; padding: 12px 14px; width: 100%; min-height: 50px; }
        .resultat { padding: 16px; border-radius: 14px; margin-top: 16px; }
        .resultat-valide { background: var(--ok-soft); color: var(--ok); }
        .resultat-utilise { background: var(--warn-soft); color: var(--warn); }
        .resultat-annule, .resultat-inconnu { background: var(--err-soft); color: var(--err); }

        .auth { max-width: 460px; margin: 0 auto; }

        @media (prefers-reduced-motion: reduce) { * { transition: none !important; animation: none !important; } }
        @media print {
            .site-top, .site-foot, .noprint, .global-msg { display: none !important; }
            body { background: #fff; }
            .wrap { padding: 0; }
        }
    </style>
    @stack('head')
</head>
<body>
    <header class="site-top" x-data="{ open: false }">
        <nav class="nav" aria-label="Navigation principale">
            <a href="{{ route('home') }}" class="brand" aria-label="CotaTrans, accueil">
                <span class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="13" rx="3"/><path d="M3 11h18"/><circle cx="7.5" cy="19.5" r="1.5"/><circle cx="16.5" cy="19.5" r="1.5"/></svg>
                </span>
                <span class="brand-name">Cota<i>Trans</i></span>
            </a>
            <button type="button" class="linkbtn menu-toggle" @click="open = !open" :aria-expanded="open" aria-controls="menu">
                <span x-text="open ? 'Fermer' : 'Menu'">Menu</span>
            </button>
            <div class="links" id="menu" :class="{ open }">
                <a href="{{ route('home') }}" @class(['on' => request()->routeIs('home', 'evenements.*')])>Événements</a>
                @auth
                    <a href="{{ route('reservations.index') }}" @class(['on' => request()->routeIs('reservations.*')])>Mes billets</a>
                    @if (auth()->user()->peutScanner())
                        <a href="{{ route('controle.scanner') }}" @class(['on' => request()->routeIs('controle.*')])>Contrôle</a>
                    @endif
                    @if (auth()->user()->estAdmin())
                        <a href="{{ url('/admin') }}">Admin</a>
                    @endif
                    <a href="{{ route('profile.edit') }}">Profil</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="linkbtn">Déconnexion</button>
                    </form>
                @else
                    <a href="{{ route('login') }}">Connexion</a>
                    <a href="{{ route('register') }}" class="pill">Créer un compte</a>
                @endauth
            </div>
        </nav>
    </header>

    <main>
        <div class="wrap">
            @if ($message = \App\Models\Setting::get('message_global'))
                <div class="alert alert-warn global-msg">{{ $message }}</div>
            @endif

            {{ $slot }}
        </div>
    </main>

    <footer class="site-foot">
        <div class="in">
            <span>© {{ date('Y') }} CotaTrans · Transport aller-retour pour vos événements</span>
            <span>Paiement Mobile Money · Billet QR code</span>
        </div>
    </footer>
    @livewireScripts
    @stack('scripts')
</body>
</html>
