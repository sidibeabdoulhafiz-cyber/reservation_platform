<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Tableau de Bord</title>
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

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 32px; }
        .stat-card { background: var(--surface); border: 1px solid var(--line); padding: 20px; border-radius: 14px; text-align: center; }
        .stat-card b { display: block; font: 500 2rem var(--mono); color: var(--accent); }
        .stat-card span { font-size: 12px; text-transform: uppercase; color: var(--muted); }

        .panel { background: var(--surface); border: 1px solid var(--line); border-radius: 14px; padding: 28px; }
        .tw { overflow-x: auto; border: 1px solid var(--line); border-radius: 10px; }
        table { border-collapse: collapse; width: 100%; font-size: 14px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid var(--line); }
        th { font: 500 11px var(--mono); text-transform: uppercase; color: var(--muted); background: var(--bg); }
        .mono { font-family: var(--mono); font-size: 13px; }

        .badge { font: 500 11px var(--mono); padding: 3px 9px; border-radius: 99px; border: 1px solid var(--line); text-transform: uppercase; }
        .badge.payee { color: var(--ok); border-color: var(--ok); }
        .badge.en_attente { color: var(--accent); border-color: var(--accent); }

        .btn-sm { font: 600 12px var(--body); padding: 6px 12px; border-radius: 6px; text-decoration: none; border: 1px solid var(--line); background: var(--bg); color: var(--fg); cursor: pointer; }
        .btn-sm:hover { border-color: var(--accent); color: var(--accent); }
        .btn-danger { color: var(--err); border-color: var(--err); }
        .btn-danger:hover { background: var(--err); color: white; }

        .admin-nav { display: flex; gap: 10px; margin-bottom: 20px; }
        .admin-nav a { font: 600 14px var(--body); padding: 8px 16px; border-radius: 99px; text-decoration: none; background: var(--surface); border: 1px solid var(--line); color: var(--fg); }
        .admin-nav a.active { background: var(--accent); color: var(--accent-fg); border-color: var(--accent); }
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
            <h1>Tableau de bord</h1>
        </header>

        <div class="admin-nav">
            <a href="{{ route('admin.dashboard') }}" class="active">Utilisateurs</a>
            <a href="{{ route('admin.zones.index') }}">Zones & Prix</a>
            <a href="{{ route('admin.settings.index') }}">Réglages Site</a>
        </div>

        @if(session('success'))
            <div style="background:var(--surface); border:1px solid var(--ok); color:var(--ok); padding:12px; border-radius:10px; margin-bottom:20px">
                {{ session('success') }}
            </div>
        @endif

        <div class="panel">
            <h2>Gestion des Utilisateurs</h2>
            <div class="tw">
                <table>
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Rôle</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td class="mono">{{ $user->email }}</td>
                                <td class="mono">{{ $user->tel }}</td>
                                <td>{{ ucfirst($user->role) }}</td>
                                <td><span class="badge {{ $user->actif ? 'payee' : 'en_attente' }}">{{ $user->actif ? 'Actif' : 'Suspendu' }}</span></td>
                                <td>
                                    <form action="{{ route('admin.users.toggle-status', $user->id) }}" method="POST" style="display:inline">
                                        @csrf @method('PATCH')
                                        <button class="btn-sm" type="submit">{{ $user->actif ? 'Suspendre' : 'Activer' }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
