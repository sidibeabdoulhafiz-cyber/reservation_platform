<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0C4173">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-icone.svg') }}">
    @php
        // ---------- SEO ----------
        $nomSite = 'CotaTrans';
        $titreComplet = isset($title) && $title
            ? $title.' · '.$nomSite
            : $nomSite.' · Transport aller-retour pour vos événements et sorties à Abidjan';
        $desc = $description ?? 'CotaTrans, le transport aller-retour de vos événements et sorties à Abidjan : concerts, festivals, matchs, journées récréatives. Réservez votre place en car, ramassage près de chez vous, paiement Mobile Money, billet QR code.';
        $urlCanonique = $canonical ?? url()->current();
        $imagePartage = $ogImage ?? asset('images/og-cotatrans.jpg');
        $prive = ($noindex ?? false) || request()->routeIs('login', 'register', 'password.*', 'verification.*', 'reservations.*', 'profile.*', 'controle.*', 'dashboard', 'dev.*');
        $gsc = \App\Models\Setting::get('google_site_verification');
    @endphp
    <title>{{ $titreComplet }}</title>
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags($desc), 160, '…') }}">
    <meta name="robots" content="{{ $prive ? 'noindex, nofollow' : 'index, follow, max-image-preview:large' }}">
    <link rel="canonical" href="{{ $urlCanonique }}">
    <meta name="application-name" content="CotaTrans">
    <meta name="apple-mobile-web-app-title" content="CotaTrans">
    <meta name="format-detection" content="telephone=no">
    <meta property="og:site_name" content="CotaTrans">
    <meta property="og:locale" content="fr_CI">
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:title" content="{{ $titreComplet }}">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit(strip_tags($desc), 200, '…') }}">
    <meta property="og:url" content="{{ $urlCanonique }}">
    <meta property="og:image" content="{{ $imagePartage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $titreComplet }}">
    <meta name="twitter:description" content="{{ \Illuminate\Support\Str::limit(strip_tags($desc), 200, '…') }}">
    <meta name="twitter:image" content="{{ $imagePartage }}">
    @if ($gsc)
        <meta name="google-site-verification" content="{{ $gsc }}">
    @endif
    <link rel="apple-touch-icon" href="{{ asset('images/logo-icone.svg') }}">
    @php
        $reseaux = collect(\App\Filament\Pages\Settings::RESEAUX)->keys()->map(fn ($k) => \App\Models\Setting::get($k))->filter()->values()->all();
        $ldBase = [
            [
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => 'CotaTrans',
                'url' => url('/'),
                'logo' => asset('images/logo-icone.svg'),
                'description' => 'Transport aller-retour en car pour les événements et sorties : concerts, festivals, matchs.',
                'areaServed' => ['@type' => 'City', 'name' => 'Abidjan'],
                'sameAs' => $reseaux,
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => 'CotaTrans',
                'url' => url('/'),
                'inLanguage' => 'fr',
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => url('/').'?q={search_term_string}#evenements',
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode(array_merge(request()->routeIs('home') ? $ldBase : [], $jsonld ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    <script>document.documentElement.classList.add('js');</script>
    @livewireStyles
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        /* =========================================================
           CotaTrans · système de design
           Bleu #0C4173 = structure · Orange #FB7212 = actions
           ========================================================= */
        :root {
            --brand: #0C4173;
            --brand-dark: #08305a;
            --brand-soft: #eaf1f9;
            --accent: #FB7212;
            --accent-dark: #e0600a;
            --accent-soft: #fff3ea;
            --accent-fg: #fff;
            --bg: #f5f7fa;
            --surface: #fff;
            --soft: #f7f9fc;
            --line: #e2e8f0;
            --fg: #0f1b2d;
            --muted: #5b6878;
            --ok: #12805c;
            --ok-soft: #e7f6ef;
            --warn: #a35f00;
            --warn-soft: #fff6e2;
            --err: #c0362c;
            --err-soft: #fdeceb;
            --ink: #0C4173;
            --ink-fg: #f3f7fc;
            --ink-muted: #a9bfd9;
            --r-sm: 10px;
            --r: 16px;
            --r-lg: 24px;
            --radius: 16px;
            --shadow-sm: 0 1px 2px rgba(15, 27, 45, .06);
            --shadow: 0 1px 2px rgba(15, 27, 45, .05), 0 6px 20px rgba(15, 27, 45, .06);
            --shadow-lg: 0 4px 10px rgba(15, 27, 45, .06), 0 20px 44px rgba(15, 27, 45, .12);
            --display: 'Outfit', system-ui, -apple-system, 'Segoe UI', sans-serif;
            --body: 'Outfit', system-ui, -apple-system, 'Segoe UI', sans-serif;
            --mono: 'DM Mono', ui-monospace, monospace;
            --tabbar-h: 0px;
        }
        *, *::before, *::after { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; scroll-behavior: smooth; scroll-padding-top: 90px; }
        body { margin: 0; color: var(--fg); font: 16px/1.55 var(--body); background: var(--bg); min-height: 100vh; display: flex; flex-direction: column; -webkit-font-smoothing: antialiased; }
        img { max-width: 100%; display: block; }
        a { color: inherit; }
        :focus-visible { outline: 2.5px solid var(--accent); outline-offset: 2px; }
        [x-cloak] { display: none !important; }
        .sr { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
        .sr:focus { position: fixed; top: 8px; left: 8px; width: auto; height: auto; clip: auto; z-index: 99; background: #fff; color: var(--brand); padding: 10px 14px; border-radius: 10px; }
        .ico { flex: none; }

        /* ---------- Typographie ---------- */
        h1 { font: 700 clamp(1.75rem, 5vw, 2.6rem)/1.12 var(--display); margin: 0 0 10px; letter-spacing: -.02em; }
        h2 { font: 700 clamp(1.3rem, 3.4vw, 1.75rem)/1.2 var(--display); margin: 0 0 12px; letter-spacing: -.01em; }
        h3 { font: 600 1.12rem/1.3 var(--display); margin: 0 0 6px; }
        p { margin: 0 0 12px; }
        .mono { font-family: var(--mono); font-size: .9em; letter-spacing: .02em; }
        .hint { color: var(--muted); font-size: 14.5px; margin: 6px 0 0; }
        .muted { color: var(--muted); }
        .kicker { font: 600 13px var(--body); text-transform: uppercase; letter-spacing: .08em; color: var(--accent-dark); margin-bottom: 8px; }

        /* ---------- En-tête ---------- */
        .site-top { background: var(--brand); color: #fff; position: sticky; top: 0; z-index: 40; border-bottom: 3px solid var(--accent); }
        .nav { display: flex; align-items: center; gap: 20px; padding: 0 20px; max-width: 1200px; margin: 0 auto; height: 72px; }
        .brand { display: inline-flex; align-items: center; flex: none; }
        .brand img { height: 42px; width: auto; }
        .nav-main { display: flex; gap: 4px; margin: 0 auto; }
        .nav-main a { font: 500 16px var(--body); color: #e4ecf6; text-decoration: none; padding: 10px 14px; border-radius: 10px; position: relative; transition: color .15s, background .15s; }
        .nav-main a:hover { color: #fff; background: rgba(255,255,255,.08); }
        .nav-main a.on { color: #fff; }
        .nav-main a.on::after { content: ''; position: absolute; left: 14px; right: 14px; bottom: 3px; height: 2px; border-radius: 2px; background: var(--accent); }
        .nav-user { display: flex; align-items: center; gap: 8px; }
        .nav-user form { margin: 0; }
        .nav-btn { font: 600 15px var(--body); color: #fff; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; padding: 9px 16px; min-height: 42px; border-radius: 12px; border: 1.5px solid transparent; background: none; cursor: pointer; transition: background .15s, border-color .15s; }
        .nav-btn:hover { background: rgba(255,255,255,.1); }
        .nav-btn.outline { border-color: rgba(255,255,255,.6); }
        .nav-btn.outline:hover { border-color: #fff; }
        .nav-btn.fill { background: var(--accent); }
        .nav-btn.fill:hover { background: var(--accent-dark); }
        .avatar { width: 32px; height: 32px; border-radius: 50%; background: var(--accent); color: #fff; display: inline-grid; place-items: center; font: 700 14px var(--body); }
        .mobile-only { display: none !important; }
        @media (max-width: 860px) {
            .nav { height: 60px; padding: 0 16px; gap: 12px; }
            .brand img { height: 34px; }
            .nav-main, .nav-user .desk { display: none !important; }
            .nav-user { margin-left: auto; }
            .mobile-only { display: inline-flex !important; }
        }

        /* Barre d'onglets mobile (style application) */
        .tabbar { display: none; }
        @media (max-width: 860px) {
            :root { --tabbar-h: 66px; }
            .tabbar { display: grid; grid-auto-flow: column; grid-auto-columns: 1fr; position: fixed; left: 0; right: 0; bottom: 0; z-index: 45; background: #fff; border-top: 1px solid var(--line); padding: 6px 6px calc(6px + env(safe-area-inset-bottom)); box-shadow: 0 -6px 20px rgba(15,27,45,.06); }
            .tabbar a { display: grid; justify-items: center; gap: 3px; padding: 6px 2px; text-decoration: none; color: var(--muted); font: 500 12px var(--body); border-radius: 12px; min-height: 52px; }
            .tabbar a.on { color: var(--brand); }
            .tabbar a.on .ico { color: var(--accent); }
            body { padding-bottom: calc(var(--tabbar-h) + env(safe-area-inset-bottom)); }
        }

        /* ---------- Structure ---------- */
        main { flex: 1; overflow-x: clip; }
        .wrap { max-width: 1200px; margin: 0 auto; padding: 28px 20px 72px; }
        @media (max-width: 600px) { .wrap { padding: 18px 16px 48px; } }
        .narrow { max-width: 620px; margin: 0 auto; }
        .medium { max-width: 920px; margin: 0 auto; }
        .bleed { margin-left: calc(50% - 50vw); margin-right: calc(50% - 50vw); }
        .bleed > .in { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .hero { margin-bottom: 22px; }
        .hero p { color: var(--muted); margin: 0; max-width: 62ch; font-size: 1.05rem; }
        .panel { background: var(--surface); border: 1px solid var(--line); border-radius: var(--r); padding: 22px; box-shadow: var(--shadow-sm); }
        @media (max-width: 600px) { .panel { padding: 18px; } }
        .page-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
        .page-head h1 { margin: 0; }
        .back { display: inline-flex; align-items: center; gap: 6px; color: var(--muted); text-decoration: none; font-weight: 500; font-size: 15px; margin-bottom: 14px; min-height: 36px; }
        .back:hover { color: var(--brand); }
        .sec-title { font: 700 clamp(1.55rem, 3.6vw, 2.2rem)/1.15 var(--display); color: var(--brand); margin: 0 0 10px; letter-spacing: -.01em; }
        .sec-lead { color: var(--muted); font-size: 1.08rem; max-width: 60ch; margin: 0 0 30px; }

        /* ---------- Boutons ---------- */
        .btn, .cta {
            font: 600 16px var(--body); border: 1.5px solid transparent; border-radius: 12px; background: var(--accent); color: var(--accent-fg);
            padding: 12px 22px; min-height: 50px; cursor: pointer; text-decoration: none;
            display: inline-flex; justify-content: center; align-items: center; gap: 10px;
            transition: background .15s, border-color .15s, transform .08s, box-shadow .15s;
        }
        .btn:hover, .cta:hover { background: var(--accent-dark); }
        .btn:active, .cta:active { transform: translateY(1px); }
        .btn[disabled], .cta[disabled] { opacity: .45; cursor: not-allowed; }
        .cta { width: 100%; justify-content: space-between; padding: 14px 20px; min-height: 56px; font-size: 1.05rem; }
        .cta.center { justify-content: center; }
        .btn-block { width: 100%; }
        .btn-lg { min-height: 58px; padding: 14px 30px; font-size: 1.08rem; border-radius: 14px; }
        .btn-sm { min-height: 40px; padding: 8px 14px; font-size: 14.5px; border-radius: 10px; }
        .btn-secondary { background: var(--brand); }
        .btn-secondary:hover { background: var(--brand-dark); }
        .btn-ghost { background: var(--surface); color: var(--brand); border-color: var(--line); }
        .btn-ghost:hover { background: var(--brand-soft); border-color: var(--brand); }
        .btn-light { background: transparent; color: #fff; border-color: rgba(255,255,255,.7); }
        .btn-light:hover { background: rgba(255,255,255,.1); border-color: #fff; }
        .btn-wa { background: #25d366; color: #073b1f; }
        .btn-wa:hover { background: #1eb956; }
        .amt { font-family: var(--mono); font-weight: 500; }
        .linklike { background: none; border: 0; padding: 0; color: var(--brand); font: inherit; font-weight: 600; cursor: pointer; text-decoration: underline; text-underline-offset: 3px; }

        /* ---------- Formulaires ---------- */
        fieldset { border: 0; padding: 0; margin: 0 0 26px; min-width: 0; }
        legend { font: 600 1.08rem var(--display); margin-bottom: 12px; padding: 0; display: flex; align-items: center; gap: 10px; color: var(--fg); }
        legend .num { width: 28px; height: 28px; border-radius: 50%; background: var(--brand); color: #fff; display: inline-grid; place-items: center; font: 600 14px var(--body); flex: none; }
        .field { display: grid; gap: 6px; font-weight: 500; font-size: 14.5px; margin: 0 0 14px; color: var(--fg); }
        .field input, .field select, .field textarea, .panel input[type=text], .panel input[type=email], .panel input[type=password], .panel input[type=number], .panel input[type=tel], .auth input[type=text], .auth input[type=email], .auth input[type=password], .auth input[type=tel] {
            font: 400 16px var(--body); background: #fff; border: 1.5px solid var(--line); border-radius: 12px; padding: 12px 14px; width: 100%; min-height: 52px; color: var(--fg); transition: border-color .15s, box-shadow .15s;
        }
        .field input::placeholder, .panel input::placeholder { color: #9aa5b4; }
        .field input:focus, .panel input:focus, .auth input:focus { border-color: var(--brand); outline: none; box-shadow: 0 0 0 4px var(--brand-soft); }
        .panel form label, .auth form label { display: grid; gap: 6px; font-weight: 500; font-size: 14.5px; margin: 0 0 14px; }
        .panel form .check, .auth form .check { display: flex; flex-direction: row; align-items: center; gap: 10px; font-weight: 400; }
        .panel form .check input, .auth form .check input { width: 20px; height: 20px; min-height: auto; accent-color: var(--accent); }
        .grid2 { display: grid; gap: 0 14px; grid-template-columns: 1fr; }
        @media (min-width: 560px) { .grid2 { grid-template-columns: 1fr 1fr; } }
        .err, .field .err { color: var(--err); font-size: 13.5px; font-weight: 500; }

        .stepper { display: inline-flex; align-items: center; border: 1.5px solid var(--line); border-radius: 14px; background: #fff; }
        .stepper button { width: 52px; height: 52px; border: 0; background: none; cursor: pointer; color: var(--brand); border-radius: 14px; display: grid; place-items: center; }
        .stepper button:hover:not([disabled]) { background: var(--brand-soft); }
        .stepper button[disabled] { opacity: .3; cursor: not-allowed; }
        .stepper output { font: 600 22px var(--display); min-width: 48px; text-align: center; }

        /* Options sélectionnables */
        .opts { display: grid; gap: 10px; }
        .opt { position: relative; display: block; }
        .opt input { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; margin: 0; z-index: 1; }
        .opt > span { display: flex; align-items: center; justify-content: space-between; gap: 12px; border: 1.5px solid var(--line); background: #fff; border-radius: 14px; padding: 14px 16px; min-height: 64px; transition: border-color .15s, background .15s; }
        .opt b { font-weight: 600; font-size: 1.05rem; display: block; }
        .opt small { color: var(--muted); display: block; }
        .opt:hover > span { border-color: #c3cdda; }
        .opt input:checked + span { border-color: var(--brand); background: var(--brand-soft); box-shadow: inset 0 0 0 1px var(--brand); }
        .opt input:focus-visible + span { outline: 2.5px solid var(--accent); outline-offset: 2px; }
        .opt input:disabled + span { opacity: .5; }
        .opt .price { font: 600 15px var(--body); white-space: nowrap; color: var(--brand); }

        /* Chips (départs, filtres) */
        .chips { display: flex; gap: 8px; overflow-x: auto; padding: 2px 2px 6px; scrollbar-width: none; }
        .chips::-webkit-scrollbar { display: none; }
        .chip { position: relative; flex: none; }
        .chip input { position: absolute; inset: 0; opacity: 0; cursor: pointer; margin: 0; }
        .chip span { display: grid; gap: 2px; padding: 10px 16px; border: 1.5px solid var(--line); border-radius: 14px; background: #fff; min-width: 140px; transition: border-color .15s, background .15s; }
        .chip b { font-weight: 600; }
        .chip small { color: var(--muted); font-size: 13px; }
        .chip input:checked + span { border-color: var(--brand); background: var(--brand-soft); box-shadow: inset 0 0 0 1px var(--brand); }
        .chip input:focus-visible + span { outline: 2.5px solid var(--accent); outline-offset: 2px; }
        .chip small.low { color: var(--err); font-weight: 600; }
        .pill-btn { font: 500 14.5px var(--body); padding: 9px 16px; min-height: 40px; border-radius: 99px; border: 1.5px solid var(--line); background: #fff; color: var(--fg); cursor: pointer; white-space: nowrap; transition: all .15s; }
        .pill-btn:hover { border-color: var(--brand); }
        .pill-btn.on { background: var(--brand); border-color: var(--brand); color: #fff; }

        /* Itinéraire (arrêts) */
        .route { list-style: none; margin: 0; padding: 0; display: grid; gap: 8px; }
        .stop { position: relative; }
        .stop input { position: absolute; inset: 0; opacity: 0; cursor: pointer; margin: 0; z-index: 1; }
        .stop .body { display: grid; grid-template-columns: 52px 20px 1fr auto; align-items: center; gap: 10px; padding: 13px 14px; border: 1.5px solid var(--line); border-radius: 14px; background: #fff; transition: border-color .15s, background .15s; }
        .stop .time { font: 600 14.5px var(--body); color: var(--brand); }
        .stop .dot { width: 14px; height: 14px; border-radius: 50%; border: 3px solid #b7c3d3; background: #fff; justify-self: center; }
        .stop.principal .dot { border-color: var(--brand); }
        .stop .name b { font-weight: 600; font-size: 1.02rem; display: block; }
        .stop .name small { color: var(--muted); display: block; font-size: 13.5px; }
        .stop .tag-main { display: inline-block; font: 600 11px var(--body); text-transform: uppercase; letter-spacing: .05em; color: var(--brand); background: var(--brand-soft); padding: 2px 8px; border-radius: 99px; margin-left: 6px; vertical-align: middle; }
        .stop .price { font: 600 15px var(--body); color: var(--fg); white-space: nowrap; }
        .stop:hover .body { border-color: #c3cdda; }
        .stop input:checked + .body { border-color: var(--accent); background: var(--accent-soft); box-shadow: inset 0 0 0 1px var(--accent); }
        .stop input:checked + .body .dot { border-color: var(--accent); background: var(--accent); }
        .stop input:focus-visible + .body { outline: 2.5px solid var(--accent); outline-offset: 2px; }
        .route-end { display: grid; grid-template-columns: 52px 20px 1fr; gap: 10px; align-items: center; padding: 8px 14px; color: var(--muted); font-size: 14.5px; }
        .route-end .ico { justify-self: center; color: var(--brand); }

        /* ---------- Alertes & badges ---------- */
        .alert { padding: 13px 16px; border-radius: 14px; margin-bottom: 18px; background: var(--surface); border: 1px solid var(--line); display: flex; gap: 12px; align-items: flex-start; }
        .alert > .ico { margin-top: 2px; }
        .alert-ok { border-color: #b7e2cf; background: var(--ok-soft); color: var(--ok); }
        .alert-warn { border-color: #f1d49a; background: var(--warn-soft); color: var(--warn); }
        .alert-err { border-color: #f2bdb8; background: var(--err-soft); color: var(--err); }
        .alert-info { border-color: #c9d9ec; background: var(--brand-soft); color: var(--brand); }
        .alert a { font-weight: 600; }
        .badge { font: 600 12.5px var(--body); padding: 5px 11px; border-radius: 99px; white-space: nowrap; display: inline-flex; align-items: center; gap: 6px; background: #eef1f5; color: var(--muted); }
        .badge.a_payer { background: var(--warn-soft); color: var(--warn); }
        .badge.en_verification { background: var(--brand-soft); color: var(--brand); }
        .badge.payee { background: var(--ok-soft); color: var(--ok); }
        .badge.annulee, .badge.expiree { background: #eef1f5; color: var(--muted); }

        /* ---------- Cartes événement ---------- */
        .cards { display: grid; gap: 22px; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); }
        .card { background: var(--surface); border-radius: var(--r-lg); overflow: hidden; border: 1px solid var(--line); box-shadow: var(--shadow-sm); text-decoration: none; display: flex; flex-direction: column; transition: transform .2s ease, box-shadow .2s ease, border-color .2s; }
        .card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); border-color: transparent; }
        .cover { position: relative; aspect-ratio: 16 / 9; background-size: cover; background-position: center; overflow: hidden; }
        .cover img { width: 100%; height: 100%; object-fit: cover; transition: transform .4s ease; }
        .card:hover .cover img { transform: scale(1.03); }
        .cover .fallback { position: absolute; inset: 0; display: grid; place-items: center; color: rgba(255,255,255,.92); font: 700 1.5rem var(--display); padding: 16px; text-align: center; }
        .date-badge { position: absolute; top: 12px; left: 12px; background: #fff; color: var(--fg); border-radius: 12px; padding: 6px 10px; text-align: center; line-height: 1.05; box-shadow: 0 4px 12px rgba(0,0,0,.15); }
        .date-badge b { display: block; font: 700 1.35rem var(--display); color: var(--brand); }
        .date-badge small { font: 600 11px var(--body); text-transform: uppercase; letter-spacing: .06em; color: var(--accent-dark); }

        /* ---------- Ticket ---------- */
        .ticket { background: var(--brand); color: var(--ink-fg); border-radius: var(--r-lg); overflow: hidden; box-shadow: 0 14px 36px rgba(12, 65, 115, .22); }
        .ticket .top { padding: 20px 22px 16px; }
        .ticket .tag { font: 600 12px var(--body); color: #ffb07a; letter-spacing: .08em; text-transform: uppercase; }
        .ticket .zone { font: 700 1.45rem/1.15 var(--display); margin: 8px 0 4px; }
        .ticket .who { color: var(--ink-muted); font-size: 14.5px; }
        .perf { border-top: 2px dashed rgba(255,255,255,.25); margin: 0 14px; position: relative; }
        .perf::before, .perf::after { content: ''; position: absolute; top: -10px; width: 20px; height: 20px; background: var(--bg); border-radius: 50%; }
        .perf::before { left: -24px; }
        .perf::after { right: -24px; }
        .ticket .bot { padding: 16px 22px 20px; display: grid; gap: 8px; }
        .line, .total { display: flex; justify-content: space-between; gap: 12px; font-size: 14.5px; }
        .line { color: var(--ink-muted); }
        .line span:last-child { color: var(--ink-fg); text-align: right; font-weight: 500; }
        .total { margin-top: 6px; padding-top: 12px; border-top: 1px solid rgba(255,255,255,.18); }
        .total span:last-child { font: 700 1.35rem var(--display); }

        /* Billet QR */
        .billet-card { break-inside: avoid; page-break-inside: avoid; }
        .billet-body { display: grid !important; grid-template-columns: 1fr; gap: 16px !important; }
        @media (min-width: 480px) { .billet-body { grid-template-columns: 180px 1fr; align-items: center; } }
        .qr-box { position: relative; text-align: center; }
        .qr-svg { background: #fff; border-radius: 14px; padding: 10px; max-width: 220px; margin: 0 auto; }
        .qr-svg svg { display: block; width: 100%; height: auto; }
        .qr-box .num { margin-top: 8px; color: var(--ink-fg); letter-spacing: .06em; }
        .stamp { position: absolute; top: 40%; left: 50%; transform: translate(-50%, -50%) rotate(-10deg); border: 3px solid var(--err); color: var(--err); background: #fff; padding: 4px 12px; font: 700 1.05rem var(--display); text-transform: uppercase; border-radius: 6px; }
        .infos { margin: 0; display: grid; gap: 10px; }
        .infos div { display: grid; gap: 1px; }
        .infos dt { color: var(--ink-muted); font-size: 12px; text-transform: uppercase; letter-spacing: .06em; }
        .infos dd { margin: 0; font-weight: 600; }
        .infos .legs, .legs { display: flex; gap: 8px; flex-wrap: wrap; }
        .leg { font: 600 12.5px var(--body); padding: 4px 10px; border-radius: 99px; background: rgba(255,255,255,.12); color: #fff; display: inline-flex; gap: 6px; align-items: center; }
        .leg.done { background: rgba(18, 128, 92, .9); }

        /* Trajet aller / retour */
            .trajet { list-style: none; margin: 0; padding: 0; display: grid; gap: 12px; }
            .trajet li { display: flex; gap: 12px; align-items: flex-start; }
            .t-tag { flex: none; font: 600 12px var(--body); text-transform: uppercase; letter-spacing: .05em; background: var(--brand); color: #fff; border-radius: 8px; padding: 4px 8px; min-width: 64px; text-align: center; margin-top: 2px; }
            .t-tag.ret { background: var(--accent); }
            .t-body { display: grid; gap: 2px; min-width: 0; }
            .t-body b { text-transform: capitalize; font-weight: 600; }
            .t-body span { color: var(--muted); font-size: 14.5px; display: inline-flex; align-items: center; gap: 6px; }
            .t-body .ico { color: var(--brand); }
            .trajet.compact { gap: 8px; }
            @media (min-width: 640px) { .trajet:not(.compact) { grid-template-columns: 1fr 1fr; } }

        /* Partage */
        .partage { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-top: 16px; }
        .partage-lbl { font-weight: 600; font-size: 14.5px; color: var(--muted); margin-right: 2px; }
        .sh { display: inline-flex; align-items: center; gap: 7px; min-height: 40px; padding: 8px 14px; border-radius: 99px; border: 1.5px solid var(--line); background: #fff; color: var(--fg); font: 600 14px var(--body); text-decoration: none; cursor: pointer; transition: transform .15s, background .15s, color .15s, border-color .15s; }
        .sh:hover { transform: translateY(-1px); }
        .sh-wa { color: #128c4a; }
        .sh-wa:hover { background: #25d366; border-color: #25d366; color: #fff; }
        .sh-fb { color: #1877f2; }
        .sh-fb:hover { background: #1877f2; border-color: #1877f2; color: #fff; }
        .sh-nat:hover, .sh-cp:hover { border-color: var(--brand); color: var(--brand); }
        .partage.compact .sh span { display: none; }
        .partage.compact .sh { padding: 8px; width: 40px; justify-content: center; }
        @media (max-width: 480px) { .partage .sh-cp span, .partage .sh-nat span { display: none; } }

        .note { margin: 0 0 12px; }
        .note-ok { color: var(--ok); }
        .barre { height: 6px; background: var(--line); border-radius: 99px; overflow: hidden; }
        .barre span { display: block; height: 100%; background: var(--accent); transition: width .2s; }

        /* Barre d'action collante en bas */
        .bar { position: sticky; bottom: 0; z-index: 10; padding: 12px 0 calc(12px + env(safe-area-inset-bottom)); background: linear-gradient(transparent, var(--bg) 30%); }
        .bar .cta { box-shadow: 0 10px 24px rgba(251, 114, 18, .32); }

        /* Scanner */
        .lecteur { min-height: 240px; background: var(--brand-dark); border-radius: var(--r-lg); overflow: hidden; margin-bottom: 16px; }
        .scan-input { font: 500 18px var(--mono); background: #fff; border: 1.5px solid var(--line); border-radius: 12px; padding: 12px 14px; width: 100%; min-height: 54px; letter-spacing: .06em; }
        .resultat { padding: 18px; border-radius: var(--r); margin-top: 16px; display: flex; gap: 14px; align-items: flex-start; }
        .resultat-valide { background: var(--ok-soft); color: var(--ok); border: 1.5px solid #9fd8be; }
        .resultat-utilise { background: var(--warn-soft); color: var(--warn); border: 1.5px solid #f1d49a; }
        .resultat-annule, .resultat-inconnu { background: var(--err-soft); color: var(--err); border: 1.5px solid #f2bdb8; }

        .auth { max-width: 460px; margin: 0 auto; }
        .empty { text-align: center; padding: 44px 20px; }
        .empty .ico { color: var(--brand); margin: 0 auto 12px; }

        /* Apparition douce au défilement */
        /* Apparition au défilement des éléments [data-anim] (statiques). Sans JS : tout reste visible. */
        [data-anim] { transition: opacity .7s ease, transform .7s cubic-bezier(.2,.7,.2,1); transition-delay: var(--d, 0s); }
        .js [data-anim]:not(.vu) { opacity: 0; transform: translateY(26px); }
        /* (CSS pur, compatible avec les mises à jour Livewire ; visible d'office si non supporté) */
        @keyframes monte { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: none; } }
        @supports (animation-timeline: view()) {
            .reveal { animation: monte linear both; animation-timeline: view(); animation-range: entry 0% entry 35%; }
        }

        /* ---------- Pied de page ---------- */
        .site-foot { background: var(--brand-dark); color: #c3d2e4; font-size: 15px; }
        .site-foot .in { max-width: 1200px; margin: 0 auto; padding: 44px 20px 24px; display: grid; gap: 28px; grid-template-columns: 1fr; }
        @media (min-width: 760px) { .site-foot .in { grid-template-columns: 1.6fr 1fr 1fr 1fr; } }
        .foot-brand img { height: 38px; width: auto; margin-bottom: 12px; }
        .foot-brand p { margin: 0; max-width: 40ch; }
        .foot-col h4 { color: #fff; font: 600 15px var(--body); margin: 0 0 12px; }
        .foot-col a { display: block; color: #c3d2e4; text-decoration: none; padding: 4px 0; }
        .foot-col a:hover { color: var(--accent); }
        .socials { display: flex; gap: 10px; margin-top: 16px; flex-wrap: wrap; }
        .socials a { width: 40px; height: 40px; border-radius: 50%; display: grid; place-items: center; background: rgba(255,255,255,.08); color: #fff; transition: background .2s, transform .2s; }
        .socials a:hover { background: var(--accent); transform: translateY(-2px); }
.wa-float { position: fixed; right: 18px; bottom: calc(18px + var(--tabbar-h) + env(safe-area-inset-bottom)); z-index: 50; width: 58px; height: 58px; border-radius: 50%; background: #25d366; color: #fff; display: grid; place-items: center; box-shadow: 0 8px 24px rgba(37, 211, 102, .45); transition: transform .2s; animation: wa-in .5s cubic-bezier(.2,1.4,.4,1) .8s both; }
        .wa-float:hover { transform: scale(1.07); }
        .wa-float::before { content: ''; position: absolute; inset: 0; border-radius: 50%; background: #25d366; z-index: -1; animation: wa-pulse 2.4s ease-out 2s 3; }
        .wa-tip { position: absolute; right: 70px; white-space: nowrap; background: #fff; color: var(--fg); font: 600 14px var(--body); padding: 8px 12px; border-radius: 10px; box-shadow: var(--shadow); opacity: 0; transform: translateX(6px); transition: opacity .2s, transform .2s; pointer-events: none; }
        .wa-float:hover .wa-tip { opacity: 1; transform: none; }
        @keyframes wa-in { from { transform: scale(0); } to { transform: scale(1); } }
        @keyframes wa-pulse { 0% { transform: scale(1); opacity: .6; } 100% { transform: scale(1.8); opacity: 0; } }
        .evt-page ~ .wa-float, body:has(.side .bar) .wa-float { bottom: calc(92px + var(--tabbar-h) + env(safe-area-inset-bottom)); }
        @media (min-width: 960px) { body:has(.side .bar) .wa-float { bottom: 24px; } }
        .cookie { position: fixed; left: 16px; right: 16px; bottom: calc(16px + var(--tabbar-h) + env(safe-area-inset-bottom)); z-index: 60; max-width: 560px; margin-left: auto; background: #fff; border: 1px solid var(--line); border-radius: var(--r); box-shadow: var(--shadow-lg); padding: 16px 18px; font-size: 14.5px; }
        .cookie p { margin: 0 0 12px; color: var(--muted); }
        .cookie b { color: var(--fg); }
        .cookie-actions { display: flex; gap: 8px; justify-content: flex-end; }
        .site-foot .copy { max-width: 1200px; margin: 0 auto; padding: 16px 20px 20px; border-top: 1px solid rgba(255,255,255,.1); font-size: 13.5px; display: flex; flex-wrap: wrap; justify-content: space-between; gap: 8px; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { transition: none !important; animation: none !important; scroll-behavior: auto !important; }
            .reveal { animation: none !important; }
            .js [data-anim]:not(.vu) { opacity: 1; transform: none; }
        }
        @media print {
            .site-top, .site-foot, .tabbar, .noprint, .global-msg { display: none !important; }
            body { background: #fff; padding: 0; }
            .wrap { padding: 0; }
        }
    </style>
    @stack('head')
</head>
<body>
    @php
        $user = auth()->user();
        $estAccueil = request()->routeIs('home');
    @endphp

    <a href="#contenu" class="sr">Aller au contenu</a>

    <header class="site-top">
        <nav class="nav" aria-label="Navigation principale">
            <a href="{{ route('home') }}" class="brand" aria-label="CotaTrans, accueil">
                <img src="{{ asset('images/logo-horizontal-blanc.svg') }}" alt="CotaTrans" width="148" height="42">
            </a>

            <div class="nav-main">
                <a href="{{ route('home') }}#evenements" @class(['on' => request()->routeIs('evenements.*')])>Événements</a>
                <a href="{{ route('home') }}#comment-ca-marche">Comment ça marche ?</a>
                @auth
                    <a href="{{ route('reservations.index') }}" @class(['on' => request()->routeIs('reservations.*')])>Mes réservations</a>
                    @if ($user->peutScanner())
                        <a href="{{ route('controle.scanner') }}" @class(['on' => request()->routeIs('controle.*')])>Contrôle</a>
                    @endif
                    @if ($user->estAdmin())
                        <a href="{{ url('/admin') }}">Administration</a>
                    @endif
                @endauth
            </div>

            <div class="nav-user">
                @auth
                    <a href="{{ route('profile.edit') }}" class="nav-btn desk" title="Mon profil">
                        <span class="avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                        {{ \Illuminate\Support\Str::of($user->name)->before(' ') }}
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="desk">
                        @csrf
                        <button type="submit" class="nav-btn outline">Déconnexion</button>
                    </form>
                    @if ($user->peutScanner())
                        <a href="{{ route('controle.scanner') }}" class="nav-btn outline mobile-only"><x-ico name="scan" size="18" /> Contrôle</a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="nav-btn desk">Connexion</a>
                    <a href="{{ route('register') }}" class="nav-btn fill">Créer un compte</a>
                @endauth
            </div>
        </nav>
    </header>

    <main id="contenu">
        <div class="wrap">
            @if ($message = \App\Models\Setting::get('message_global'))
                <div class="alert alert-warn global-msg"><x-ico name="info" />{{ $message }}</div>
            @endif

            @if (session('status') && request()->routeIs('home'))
                <div class="alert alert-ok" role="status" style="margin-top:16px"><x-ico name="check-circle" /> {{ session('status') }}</div>
            @endif

            {{ $slot }}
        </div>
    </main>

    @php
        $liensReseaux = collect(\App\Filament\Pages\Settings::RESEAUX)
            ->map(fn ($lib, $cle) => ['nom' => substr($cle, 7), 'lib' => explode(' (', $lib)[0], 'url' => \App\Models\Setting::get($cle)])
            ->filter(fn ($r) => filled($r['url']))->values();
        $telContact = \App\Models\Setting::get('contact_tel');
    @endphp
    <footer class="site-foot">
        <div class="in">
            <div class="foot-brand">
                <img src="{{ asset('images/logo-horizontal-blanc.svg') }}" alt="CotaTrans" width="134" height="38" loading="lazy">
                <p>Le transport aller-retour de tes événements et sorties à Abidjan : concerts, festivals, matchs. Ramassage près de chez toi, un billet QR par passager.</p>
                @if ($liensReseaux->isNotEmpty())
                    <div class="socials" aria-label="CotaTrans sur les réseaux sociaux">
                        @foreach ($liensReseaux as $r)
                            <a href="{{ $r['url'] }}" target="_blank" rel="noopener me" aria-label="CotaTrans sur {{ $r['lib'] }}" title="{{ $r['lib'] }}"><x-social :name="$r['nom']" /></a>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="foot-col">
                <h4>Voyager</h4>
                <a href="{{ route('home') }}#evenements">Événements</a>
                <a href="{{ route('home') }}#comment-ca-marche">Comment ça marche ?</a>
                <a href="{{ route('home') }}#avantages">Pourquoi CotaTrans</a>
                @auth
                    <a href="{{ route('reservations.index') }}">Mes réservations</a>
                @else
                    <a href="{{ route('register') }}">Créer un compte</a>
                @endauth
            </div>
            <div class="foot-col">
                <h4>Organisateurs</h4>
                <a href="{{ route('organisateurs') }}">Proposer un événement</a>
                <a href="{{ route('organisateurs') }}#avantages-orga">Transport pour votre public</a>
            </div>
            <div class="foot-col">
                <h4>Aide</h4>
                <a href="{{ route('contact') }}">Nous contacter</a>
                @if ($telContact)
                    <a href="tel:{{ preg_replace('/[^\d+]/', '', $telContact) }}">{{ $telContact }}</a>
                @endif
                <a href="{{ route('confidentialite') }}">Confidentialité et cookies</a>
            </div>
        </div>
        <div class="copy">
            <span>© {{ date('Y') }} CotaTrans · Transport événementiel à Abidjan</span>
            <span>Paiement Mobile Money · Billet QR code</span>
        </div>
    </footer>

    @php
        $ga4 = \App\Models\Setting::get('ga4_id');
        $pixel = \App\Models\Setting::get('meta_pixel_id');
    @endphp
    @if ($ga4 || $pixel)
        {{-- Consentement cookies : la mesure d'audience n'est chargée qu'après accord. --}}
        <div class="cookie" x-data="{
                choix: (() => { try { return localStorage.getItem('ct_cookies'); } catch (e) { return null; } })(),
                ouvert: false,
                init() { this.ouvert = this.choix === null; if (this.choix === 'oui') this.charger(); window.addEventListener('ct-cookies', () => this.ouvert = true); },
                decider(v) { this.choix = v; this.ouvert = false; try { localStorage.setItem('ct_cookies', v); } catch (e) {} if (v === 'oui') this.charger(); },
                charger() {
                    if (window.__ctMesure) return; window.__ctMesure = true;
                    @if ($ga4)
                        const g = document.createElement('script'); g.async = true; g.src = 'https://www.googletagmanager.com/gtag/js?id={{ $ga4 }}'; document.head.appendChild(g);
                        window.dataLayer = window.dataLayer || []; window.gtag = function () { dataLayer.push(arguments); };
                        gtag('js', new Date()); gtag('config', '{{ $ga4 }}', { anonymize_ip: true });
                    @endif
                    @if ($pixel)
                        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
                        fbq('init', '{{ $pixel }}'); fbq('track', 'PageView');
                    @endif
                }
            }" x-show="ouvert" x-cloak x-transition.opacity role="dialog" aria-label="Cookies">
            <p><b>Cookies</b> · On utilise des cookies de mesure d’audience pour améliorer CotaTrans et nos publicités. Les cookies indispensables (connexion, réservation) restent actifs dans tous les cas. <a href="{{ route('confidentialite') }}#cookies">En savoir plus</a></p>
            <div class="cookie-actions">
                <button type="button" class="btn btn-ghost btn-sm" @click="decider('non')">Refuser</button>
                <button type="button" class="btn btn-sm" @click="decider('oui')">Accepter</button>
            </div>
        </div>
    @endif

    @php
        $waNumero = preg_replace('/\D/', '', (string) \App\Models\Setting::get('whatsapp_numero'));
        $waTexte = \App\Models\Setting::get('whatsapp_message') ?: 'Bonjour CotaTrans, j’ai une question.';
    @endphp
    @if (strlen($waNumero) >= 8 && ! request()->routeIs('controle.*'))
        <a href="https://wa.me/{{ $waNumero }}?text={{ rawurlencode($waTexte.(request()->routeIs('evenements.show') ? ' ('.url()->current().')' : '')) }}"
           class="wa-float noprint" target="_blank" rel="noopener" aria-label="Nous écrire sur WhatsApp" title="Une question ? Écris-nous sur WhatsApp">
            <x-social name="whatsapp" size="30" />
            <span class="wa-tip">Une question ?</span>
        </a>
    @endif

    {{-- Barre d'onglets mobile --}}
    <nav class="tabbar" aria-label="Navigation mobile">
        <a href="{{ route('home') }}" @class(['on' => $estAccueil])><x-ico name="home" size="22" />Accueil</a>
        <a href="{{ route('home') }}#evenements" @class(['on' => request()->routeIs('evenements.*')])><x-ico name="search" size="22" />Événements</a>
        @auth
            <a href="{{ route('reservations.index') }}" @class(['on' => request()->routeIs('reservations.*')])><x-ico name="ticket" size="22" />Réservations</a>
            <a href="{{ route('profile.edit') }}" @class(['on' => request()->routeIs('profile.*')])><x-ico name="user" size="22" />Compte</a>
        @else
            <a href="{{ route('login') }}" @class(['on' => request()->routeIs('login', 'register')])><x-ico name="user" size="22" />Connexion</a>
        @endauth
    </nav>

    {{-- Lecture automatique des reçus (doit être chargé avant Alpine) --}}
    <script src="{{ asset('js/cotatrans-preuve.js') }}?v={{ @filemtime(public_path('js/cotatrans-preuve.js')) }}"></script>
    @livewireScripts
    <script>
        // Apparition douce au défilement : un léger décalage entre éléments voisins.
        (() => {
            const io = 'IntersectionObserver' in window ? new IntersectionObserver((entrees) => {
                entrees.forEach((e) => { if (e.isIntersecting) { e.target.classList.add('vu'); io.unobserve(e.target); } });
            }, { threshold: .12, rootMargin: '0px 0px -40px 0px' }) : null;
            const brancher = () => document.querySelectorAll('[data-anim]:not(.vu):not([data-obs])').forEach((el) => {
                el.dataset.obs = '1';
                const freres = [...el.parentElement.children].filter((c) => c.hasAttribute('data-anim'));
                el.style.setProperty('--d', (Math.min(freres.indexOf(el), 5) * 0.08) + 's');
                io ? io.observe(el) : el.classList.add('vu');
            });
            document.addEventListener('DOMContentLoaded', brancher);
            document.addEventListener('livewire:navigated', brancher);
        })();
    </script>
    @stack('scripts')
</body>
</html>
