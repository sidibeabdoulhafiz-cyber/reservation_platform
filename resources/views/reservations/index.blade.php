<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Réservations - Véhicule Abidjan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #edf2ef; --surface: #ffffff; --fg: #10231d; --muted: #5b6f67; --line: #d3dfd9;
            --accent: #e5590a; --accent-fg: #ffffff; --green: #0f6e4a; --err: #b42318; --ok: #0f6e4a;
            --display: 'Bricolage Grotesque', system-ui, sans-serif; --body: 'DM Sans', system-ui, sans-serif; --mono: 'DM Mono', ui-monospace, monospace;
        }
        body { background: var(--bg); color: var(--fg); font: 16px/1.5 var(--body); margin: 0; padding: 0; }
        .wrap { max-width: 1040px; margin: 0 auto; padding: 32px 16px; }
        .nav { display: flex; justify-content: space-between; align-items: center; padding-bottom: 16px; border-bottom: 1px solid var(--line); margin-bottom: 28px; }
        .nav .brand { font: 800 1.15rem var(--display); text-decoration: none; color: var(--fg); }
        .nav .links { display: flex; gap: 10px; align-items: center; }
        .nav a { font: 600 14px var(--body); text-decoration: none; color: var(--fg); padding: 8px 12px; border-radius: 8px; }
        .nav a:hover { background: var(--surface); border: 1px solid var(--line); }

        header { margin-bottom: 28px; }
        .eyebrow { font: 500 12px var(--mono); text-transform: uppercase; color: var(--accent); letter-spacing: .14em; }
        h1 { font: 800 2rem var(--display); margin: 0; }

        .panel { background: var(--surface); border: 1px solid var(--line); border-radius: 14px; padding: 28px; }
        .tw { overflow-x: auto; border: 1px solid var(--line); border-radius: 10px; }
        table { border-collapse: collapse; width: 100%; font-size: 14px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid var(--line); }
        th { font: 500 11px var(--mono); text-transform: uppercase; color: var(--muted); background: var(--bg); }
        .mono { font-family: var(--mono); font-size: 13px; }

        .badge { font: 500 11px var(--mono); padding: 3px 9px; border-radius: 99px; border: 1px solid var(--line); text-transform: uppercase; }
        .badge.en_attente { color: var(--accent); border-color: var(--accent); }
        .badge.payee { color: var(--ok); border-color: var(--ok); }
        .badge.annulee { color: var(--err); border-color: var(--err); }

        .up-box { display: flex; flex-direction: column; gap: 8px; background: var(--bg); padding: 10px; border-radius: 8px; border: 1px solid var(--line); width: max-content; }
        .up-box label { font: 600 12px var(--body); cursor: pointer; background: var(--accent); color: var(--accent-fg); padding: 5px 10px; border-radius: 5px; text-align: center; }
        .up-box input { display: none; }
        .up-msg { font-size: 11px; color: var(--muted); }

        .btn-new { font: 600 14px var(--body); background: var(--accent); color: var(--accent-fg); padding: 10px 20px; border-radius: 8px; text-decoration: none; display: inline-block; margin-top: 20px; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5.1.1/dist/tesseract.min.js"></script>
</head>
<body>
    <div class="wrap">
        <nav class="nav">
            <a href="/" class="brand">Véhicule<i>.</i>Abidjan</a>
            <div class="links">
                <a href="{{ route('reservations.index') }}">Mes Réservations</a>
                <a href="{{ route('profile.edit') }}">Profil</a>
                @if(auth()->user()->role === 'admin')
                    <a href="{{ route('admin.dashboard') }}">Admin</a>
                @endif
                <form method="POST" action="{{ route('logout') }}" style="display:inline">
                    @csrf
                    <button type="submit" style="background:none; border:0; cursor:pointer; font:600 14px var(--body); color:var(--fg)">Déconnexion</button>
                </form>
            </div>
        </nav>

        <header>
            <span class="eyebrow">Mon compte</span>
            <h1>Mes réservations</h1>
        </header>

        <div class="panel">
            <div class="tw">
                <table>
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Date</th>
                            <th>Zone</th>
                            <th>Places</th>
                            <th>Total</th>
                            <th>Statut</th>
                            <th>Paiement</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reservations as $r)
                            <tr>
                                <td class="mono">{{ $r->ref }}</td>
                                <td>{{ $r->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $r->zone->nom }}</td>
                                <td>{{ $r->places }}</td>
                                <td class="mono">{{ number_format($r->montant, 0, ',', ' ') }} FCFA</td>
                                <td><span class="badge {{ $r->statut }}">{{ $r->statut == 'payee' ? 'Payée' : ($r->statut == 'annulee' ? 'Annulée' : 'En cours') }}</span></td>
                                <td>
                                    @if($r->statut === 'en_attente')
                                        <div class="up-box" id="up-{{ $r->id }}">
                                            <label>
                                                Téléverser reçu
                                                <input type="file" accept="image/*" onchange="handleUpload(this, '{{ $r->id }}', '{{ $r->ref }}', {{ $r->montant }})">
                                            </label>
                                            <span class="up-msg">OCR en attente...</span>
                                        </div>
                                    @elseif($r->statut === 'payee')
                                        <div class="mono">ID: {{ $r->paiement_id }}</div>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align:center; padding:40px; color:var(--muted)">Aucune réservation pour le moment.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <a href="/" class="btn-new">Nouvelle réservation</a>
        </div>
    </div>

    <script>
        async function handleUpload(input, id, ref, montantAttendu) {
            const file = input.files[0];
            if (!file) return;

            const box = document.getElementById('up-' + id);
            const msg = box.querySelector('.up-msg');
            msg.textContent = 'Lecture de la capture...';

            try {
                const worker = await Tesseract.createWorker('eng');
                const { data: { text } } = await worker.recognize(file);
                await worker.terminate();

                // Extraction simplifiée de l'ID (Format T_XXXX)
                const idMatch = text.match(/\bT_[A-Za-z0-9]{6,24}\b/);
                const paymentId = idMatch ? idMatch[0].toUpperCase() : null;

                if (!paymentId) {
                    msg.textContent = 'ID de transaction non trouvé.';
                    msg.style.color = 'var(--err)';
                    return;
                }

                // Envoi au serveur pour validation
                const formData = new FormData();
                formData.append('paiement_id', paymentId);
                formData.append('capture', file);

                const response = await fetch(`/reservations/${id}/confirm-payment`, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });

                const result = await response.json();
                if (result.success) {
                    location.reload();
                } else {
                    msg.textContent = result.message || 'Erreur de validation.';
                    msg.style.color = 'var(--err)';
                }
            } catch (e) {
                msg.textContent = 'Erreur lors de la lecture.';
                msg.style.color = 'var(--err)';
            }
        }
    </script>
</body>
</html>
