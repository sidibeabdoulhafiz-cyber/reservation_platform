<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Véhicule Abidjan' }}</title>
    @livewireStyles
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #e8efe9;
            --surface: #fff;
            --fg: #10231d;
            --muted: #5b6f67;
            --line: #cdd9d2;
            --accent: #c2410c;
            --accent-fg: #fff;
            --ok: #0f6e4a;
            --err: #b42318;
            --warn: #b54708;
            --ticket: #10231d;
            --ticket-fg: #eef5f1;
            --ticket-muted: #9db5aa;
            --display: 'Bricolage Grotesque', system-ui, sans-serif;
            --body: 'DM Sans', system-ui, sans-serif;
            --mono: 'DM Mono', ui-monospace, monospace;
        }
        *, *::before, *::after { box-sizing: border-box; }
        html, body { min-height: 100%; }
        body {
            margin: 0;
            color: var(--fg);
            font: 16px/1.5 var(--body);
            background:
                radial-gradient(720px 280px at 100% -40px, rgba(194, 65, 12, .18), transparent 55%),
                var(--bg);
        }
        a:focus-visible, button:focus-visible, input:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
        .wrap { max-width: 440px; margin: 0 auto; padding: 0 16px 112px; }
        .mono { font-family: var(--mono); font-size: 13px; }
        .hint { color: var(--muted); font-size: 14px; margin: 6px 0 0; }

        .top { background: var(--fg); color: var(--ticket-fg); margin-bottom: 22px; }
        .nav { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; padding: 14px 16px; max-width: 440px; margin: 0 auto; }
        .brand { font: 800 1.05rem var(--display); text-decoration: none; color: inherit; letter-spacing: -.02em; }
        .brand i { color: #f4a574; font-style: normal; }
        .links { display: flex; gap: 2px; align-items: center; flex-wrap: wrap; }
        .links form { margin: 0; }
        .links a, .linkbtn {
            font: 600 13px var(--body); color: var(--ticket-fg); text-decoration: none;
            padding: 10px 10px; min-height: 44px; border-radius: 8px; background: none; border: 0; cursor: pointer;
        }
        .links a:hover, .linkbtn:hover { background: rgba(255,255,255,.08); }

        .hero { margin-bottom: 18px; }
        .steps { display: flex; gap: 6px; margin: 0 0 12px; padding: 0; list-style: none; }
        .steps li {
            flex: 1; text-align: center; font: 600 11px var(--body); letter-spacing: .04em; text-transform: uppercase;
            color: var(--muted); padding: 8px 4px; border-bottom: 2px solid var(--line);
        }
        .steps li.on { color: var(--accent); border-color: var(--accent); }
        h1 { font: 800 clamp(1.85rem, 7vw, 2.4rem)/1.05 var(--display); margin: 0 0 8px; letter-spacing: -.03em; }
        .hero p { color: var(--muted); margin: 0; }

        .panel { background: var(--surface); border: 1px solid var(--line); border-radius: 16px; padding: 20px; }
        fieldset { border: 0; padding: 0; margin: 0 0 22px; }
        legend { font: 700 1.05rem var(--display); margin-bottom: 10px; padding: 0; }
        .stepper { display: inline-flex; align-items: center; border: 1.5px solid var(--line); border-radius: 12px; background: var(--bg); }
        .stepper button { width: 52px; height: 52px; border: 0; background: none; font: 600 24px var(--body); cursor: pointer; }
        .stepper output { font: 500 20px var(--mono); min-width: 48px; text-align: center; }
        .opts { display: grid; gap: 10px; }
        .opt { position: relative; display: block; }
        .opt input { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; margin: 0; }
        .opt span { display: flex; flex-direction: column; border: 1.5px solid var(--line); background: var(--bg); border-radius: 12px; padding: 14px 16px; min-height: 64px; }
        .opt b { font-family: var(--display); font-size: 1.08rem; }
        .opt small { color: var(--muted); }
        .opt input:checked + span { border-color: var(--accent); background: #fff8f4; box-shadow: inset 0 0 0 1px var(--accent); }
        .opt input:checked + span b { color: var(--accent); }
        .opt input:focus-visible + span { outline: 2px solid var(--accent); outline-offset: 2px; }

        .cta, .btn {
            font: 700 1rem var(--display); border: 0; border-radius: 12px; background: var(--accent); color: var(--accent-fg);
            padding: 12px 18px; min-height: 48px; cursor: pointer; text-decoration: none;
            display: inline-flex; justify-content: center; align-items: center; gap: 12px;
        }
        .cta { width: 100%; justify-content: space-between; padding: 16px 20px; }
        .cta[disabled], .btn[disabled] { opacity: .5; cursor: not-allowed; }
        .amt { font-family: var(--mono); font-weight: 500; }
        .btn-ghost { background: none; color: var(--fg); border: 1.5px solid var(--line); }

        .ticket { background: var(--ticket); color: var(--ticket-fg); border-radius: 16px; overflow: hidden; box-shadow: 0 16px 40px rgba(16, 35, 29, .22); }
        .ticket .top { padding: 22px 22px 18px; }
        .ticket .tag { font: 500 12px var(--mono); color: #f4a574; letter-spacing: .08em; text-transform: uppercase; }
        .ticket .zone { font: 800 1.85rem var(--display); margin: 8px 0 4px; letter-spacing: -.03em; }
        .ticket .who { color: var(--ticket-muted); font-size: 14px; }
        .perf { border-top: 2px dashed #3a5249; margin: 0 14px; position: relative; }
        .perf::before, .perf::after {
            content: ''; position: absolute; top: -9px; width: 18px; height: 18px; background: var(--bg); border-radius: 50%;
        }
        .perf::before { left: -23px; }
        .perf::after { right: -23px; }
        .ticket .bot { padding: 16px 22px 20px; display: grid; gap: 8px; }
        .line, .total { display: flex; justify-content: space-between; font-size: 14px; }
        .line { color: var(--ticket-muted); }
        .line span:last-child { font-family: var(--mono); color: var(--ticket-fg); }
        .total { margin-top: 6px; padding-top: 12px; border-top: 1px solid #3a5249; }
        .total span:last-child { font: 500 1.35rem var(--mono); }

        .alert { padding: 12px 14px; border-radius: 12px; margin-bottom: 16px; background: var(--surface); border: 1px solid var(--line); }
        .alert-ok { border-color: var(--ok); color: var(--ok); }
        .alert-warn { border-color: var(--warn); color: var(--warn); }
        .alert-err { border-color: var(--err); color: var(--err); }
        .err { color: var(--err); font-size: 14px; }

        .rhead { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
        .rbody { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin: 16px 0; }
        .rbody .k { display: block; color: var(--muted); font-size: 12px; text-transform: uppercase; letter-spacing: .04em; }
        .badge { font: 600 12px var(--body); padding: 6px 10px; border-radius: 99px; border: 1px solid var(--line); white-space: nowrap; }
        .badge.a_payer { color: var(--accent); border-color: var(--accent); }
        .badge.en_verification { color: var(--warn); border-color: var(--warn); }
        .badge.payee { color: var(--ok); border-color: var(--ok); }
        .note { margin: 0 0 12px; }
        .note-ok { color: var(--ok); }
        .proof { display: grid; gap: 12px; margin-top: 16px; padding-top: 16px; border-top: 1px dashed var(--line); }
        .proof label { display: grid; gap: 6px; font-weight: 600; font-size: 14px; }
        .proof input[type=text], .proof input[type=file], .proof input[type=number],
        .panel input[type=text], .panel input[type=email], .panel input[type=password], .panel input[type=number],
        .panel input[type=tel] { font: inherit; background: var(--bg); border: 1.5px solid var(--line); border-radius: 10px; padding: 12px 14px; width: 100%; min-height: 48px; }
        .panel form label { display: grid; gap: 6px; font-weight: 600; font-size: 14px; margin: 0 0 14px; }
        .panel form .check { display: flex; flex-direction: row; align-items: center; gap: 8px; font-weight: 500; }
        .panel form .check input { width: auto; min-height: auto; }
        .panel h2 { font: 700 1.15rem var(--display); margin: 0 0 6px; }
        .drop {
            position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 8px; min-height: 88px; border: 2px dashed var(--line); border-radius: 12px; padding: 16px;
            background: var(--bg); cursor: pointer; font-weight: 600; text-align: center;
        }
        .drop:hover, .drop:focus-within { border-color: var(--accent); }
        .drop input { position: absolute; width: 1px; height: 1px; opacity: 0; }

        .canaux { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 20px; }
        .canal { min-height: 48px; border: 1.5px solid var(--line); border-radius: 12px; background: var(--bg); font: 700 13px var(--display); cursor: pointer; }
        .canal.on { border-color: var(--accent); color: var(--accent); background: #fff8f4; box-shadow: inset 0 0 0 1px var(--accent); }

        .commande { margin-bottom: 24px; }
        .billets { display: grid; gap: 16px; margin-top: 12px; }
        .qr { display: grid; grid-template-columns: 112px 1fr; gap: 12px; align-items: center; }
        .qr-svg { background: #fff; border-radius: 10px; padding: 6px; }
        .qr-svg svg { display: block; width: 100%; height: auto; }
        .barre { height: 6px; background: var(--line); border-radius: 99px; overflow: hidden; }
        .barre span { display: block; height: 100%; background: var(--accent); }

        .bar { position: sticky; bottom: 0; z-index: 5; padding: 12px 0 calc(10px + env(safe-area-inset-bottom)); background: linear-gradient(transparent, var(--bg) 32%); }
        .bar .cta { min-height: 56px; }

        .lecteur { min-height: 240px; background: #10231d; border-radius: 16px; overflow: hidden; margin-bottom: 16px; }
        .scan-input { font: inherit; background: var(--bg); border: 1.5px solid var(--line); border-radius: 10px; padding: 12px 14px; width: 100%; min-height: 48px; }
        .resultat { padding: 16px; border-radius: 12px; margin-top: 16px; }
        .resultat-valide { background: #e7f6ee; color: var(--ok); }
        .resultat-utilise { background: #fff6e8; color: var(--warn); }
        .resultat-annule, .resultat-inconnu { background: #fdecec; color: var(--err); }

        .recap { margin: 8px 0 20px; }

        @media (min-width: 860px) {
            .wrap, .nav { max-width: 520px; }
        }
        @media (prefers-reduced-motion: reduce) { * { transition: none !important; animation: none !important; } }
    </style>
</head>
<body>
    <header class="top">
        <nav class="nav">
            <a href="{{ route('home') }}" class="brand">Véhicule<i>.</i>Abidjan</a>
            <div class="links">
                @auth
                    @if (auth()->user()->peutScanner())
                        <a href="{{ route('controle.scanner') }}">Contrôle</a>
                    @endif
                    <a href="{{ route('profile.edit') }}">Profil</a>
                    @if (auth()->user()->estAdmin())
                        <a href="{{ url('/admin') }}">Admin</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="linkbtn">Sortir</button>
                    </form>
                @else
                    <a href="{{ route('login') }}">Connexion</a>
                    <a href="{{ route('register') }}">Inscription</a>
                @endauth
            </div>
        </nav>
    </header>

    <div class="wrap">
        @if ($message = \App\Models\Setting::get('message_global'))
            <div class="alert">{{ $message }}</div>
        @endif

        {{ $slot }}
    </div>
    @livewireScripts
</body>
</html>
