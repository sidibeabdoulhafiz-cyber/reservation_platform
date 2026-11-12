<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réglages - Admin Véhicule Abidjan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #edf2ef; --surface: #ffffff; --fg: #10231d; --muted: #5b6f67; --line: #d3dfd9;
            --accent: #e5590a; --accent-fg: #ffffff; --green: #0f6e4a; --err: #b42318; --ok: #0f6e4a;
            --display: 'Bricolage Grotesque', system-ui, sans-serif; --body: 'DM Sans', system-ui, sans-serif; --mono: 'DM Mono', ui-monospace, monospace;
        }
        body { background: var(--bg); color: var(--fg); font: 16px/1.5 var(--body); margin: 0; padding: 0; }
        .wrap { max-width: 1200px; margin: 0 auto; padding: 32px 16px; }
        .nav { display: flex; justify-content: space-between; align-items: center; padding-bottom: 16px; border-bottom: 1px solid var(--line); margin-bottom: 28px; }
        .nav .brand { font: 800 1.15rem var(--display); text-decoration: none; color: var(--fg); }
        .nav .links { display: flex; gap: 10px; align-items: center; }
        .nav a { font: 600 14px var(--body); text-decoration: none; color: var(--fg); padding: 8px 12px; border-radius: 8px; }
        .nav a:hover { background: var(--surface); border: 1px solid var(--line); }

        header { margin-bottom: 28px; }
        .eyebrow { font: 500 12px var(--mono); text-transform: uppercase; color: var(--accent); letter-spacing: .14em; }
        h1 { font: 800 2rem var(--display); margin: 0; }

        .admin-nav { display: flex; gap: 10px; margin-bottom: 20px; }
        .admin-nav a { font: 600 14px var(--body); padding: 8px 16px; border-radius: 99px; text-decoration: none; background: var(--surface); border: 1px solid var(--line); color: var(--fg); }
        .admin-nav a.active { background: var(--accent); color: var(--accent-fg); border-color: var(--accent); }

        .panel { background: var(--surface); border: 1px solid var(--line); border-radius: 14px; padding: 28px; max-width: 600px; margin: 0 auto; }
        .field { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
        label { font-weight: 600; font-size: 14px; }
        input { font: inherit; background: var(--bg); border: 1.5px solid var(--line); border-radius: 8px; padding: 12px 14px; width: 100%; }
        .btn { font: 700 1rem var(--display); padding: 12px 20px; border-radius: 10px; cursor: pointer; text-decoration: none; display: inline-block; border: 0; width: 100%; text-align: center; }
        .btn-primary { background: var(--accent); color: var(--accent-fg); }
    </style>
</head>
<body>
    <div class="wrap">
        <nav class="nav">
            <a href="/" class="brand">Véhicule<i>.</i>Abidjan <span style="color:var(--accent)">Admin</span></a>
            <div class="links">
                <a href="/">Retour Site</a>
                <form method="POST" action="{{ route('logout') }}" style="display:inline">
                    @csrf
                    <button type="submit" style="background:none; border:0; cursor:pointer; font:600 14px var(--body); color:var(--fg)">Déconnexion</button>
                </form>
            </div>
        </nav>

        <header>
            <span class="eyebrow">Gestion administrative</span>
            <h1>Réglages du site</h1>
        </header>

        <div class="admin-nav">
            <a href="{{ route('admin.dashboard') }}">Utilisateurs</a>
            <a href="{{ route('admin.zones.index') }}">Zones & Prix</a>
            <a href="{{ route('admin.settings.index') }}" class="active">Réglages Site</a>
        </div>

        @if(session('success'))
            <div style="background:var(--surface); border:1px solid var(--ok); color:var(--ok); padding:12px; border-radius:10px; margin-bottom:20px">
                {{ session('success') }}
            </div>
        @endif

        <div class="panel">
            <form action="{{ route('admin.settings.update') }}" method="POST">
                @csrf
                <div class="field">
                    <label>Lien de paiement Wave</label>
                    <input type="url" name="wave_link" value="{{ $settings['wave_link'] ?? '' }}" required>
                </div>
                <div class="field">
                    <label>Places maximum par réservation</label>
                    <input type="number" name="max_places" value="{{ $settings['max_places'] ?? 8 }}" required>
                </div>
                <div class="field">
                    <label>Message global (facultatif)</label>
                    <input type="text" name="message_global" value="{{ $settings['message_global'] ?? '' }}">
                </div>
                <div class="field" style="flex-direction:row; align-items:center; gap:10px">
                    <input type="checkbox" name="site_ouvert" value="1" {{ ($settings['site_ouvert'] ?? '1') == '1' ? 'checked' : '' }} style="width:auto">
                    <label style="margin-bottom:0">Réservations ouvertes</label>
                </div>
                <button class="btn btn-primary" type="submit">Enregistrer les réglages</button>
            </form>
        </div>
    </div>
</body>
</html>
