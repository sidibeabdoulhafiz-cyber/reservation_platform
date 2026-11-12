<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservation Places Véhicule</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #edf2ef; --surface: #ffffff; --fg: #10231d; --muted: #5b6f67; --line: #d3dfd9;
            --accent: #e5590a; --accent-fg: #ffffff; --green: #0f6e4a; --ticket: #10231d; --ticket-fg: #eef5f1; --ticket-muted: #9db5aa;
            --err: #b42318; --ok: #0f6e4a;
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
        h1 { font: 800 clamp(2rem, 5.5vw, 3.4rem)/1.02 var(--display); margin: 0; letter-spacing: -.02em; }
        header p { color: var(--muted); margin: 0; max-width: 56ch; }

        .grid { display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start; }
        .panel { background: var(--surface); border: 1px solid var(--line); border-radius: 14px; padding: 28px; }
        fieldset { border: 0; padding: 0; margin: 0 0 28px; }
        legend { font: 700 1.15rem var(--display); margin-bottom: 14px; display: flex; align-items: center; gap: 10px; }
        legend .n { font: 500 12px var(--mono); background: var(--accent); color: var(--accent-fg); padding: 3px 9px; border-radius: 99px; }

        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .field { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
        label { font-weight: 600; font-size: 14px; }
        input { font: inherit; background: var(--bg); border: 1.5px solid var(--line); border-radius: 8px; padding: 12px 14px; width: 100%; }

        .stepper { display: inline-flex; align-items: center; border: 1.5px solid var(--line); border-radius: 8px; background: var(--bg); width: max-content; }
        .stepper button { width: 48px; height: 48px; border: 0; background: none; font: 600 22px var(--body); cursor: pointer; }
        .stepper output { font: 500 20px var(--mono); min-width: 44px; text-align: center; }

        .opts { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; }
        .opt { position: relative; }
        .opt input { position: absolute; opacity: 0; inset: 0; cursor: pointer; }
        .opt span { display: flex; flex-direction: column; border: 1.5px solid var(--line); background: var(--bg); border-radius: 10px; padding: 12px 14px; height: 100%; }
        .opt input:checked + span { border-color: var(--accent); box-shadow: inset 0 0 0 1px var(--accent); }
        .opt input:checked + span b { color: var(--accent); }
        .opt b { font-family: var(--display); font-size: 1.05rem; }

        .cta { font: 700 1.05rem var(--display); width: 100%; border: 0; border-radius: 10px; background: var(--accent); color: var(--accent-fg); padding: 16px 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
        .cta .amt { font-family: var(--mono); font-weight: 500; }

        .ticket { background: var(--ticket); color: var(--ticket-fg); border-radius: 14px; overflow: hidden; }
        .ticket .top { padding: 22px 24px 20px; }
        .ticket .tag { font: 500 11px var(--mono); text-transform: uppercase; color: var(--ticket-muted); }
        .ticket .zone { font: 800 2.2rem var(--display); margin: 8px 0 2px; }
        .ticket .who { color: var(--ticket-muted); font-size: 14px; min-height: 21px; }
        .perf { height: 0; border-top: 2px dashed var(--ticket-muted); margin: 0 14px; position: relative; }
        .ticket .bot { padding: 18px 24px 22px; display: grid; gap: 8px; }
        .line { display: flex; justify-content: space-between; font-size: 14px; color: var(--ticket-muted); }
        .line span:last-child { font-family: var(--mono); color: var(--ticket-fg); }
        .total { display: flex; justify-content: space-between; margin-top: 6px; padding-top: 12px; border-top: 1px solid var(--ticket-muted); }
        .total span:last-child { font: 500 1.5rem var(--mono); color: var(--ticket-fg); }

        @media (max-width: 860px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="wrap">
        <nav class="nav">
            <a href="/" class="brand">Véhicule<i>.</i>Abidjan</a>
            <div class="links">
                @auth
                    <a href="{{ route('reservations.index') }}">Mes Réservations</a>
                    <a href="{{ route('profile.edit') }}">Profil</a>
                    @if(auth()->user()->role === 'admin')
                        <a href="{{ route('admin.dashboard') }}">Admin</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" style="display:inline">
                        @csrf
                        <button type="submit" style="background:none; border:0; cursor:pointer; font:600 14px var(--body); color:var(--fg)">Déconnexion</button>
                    </form>
                @else
                    <a href="{{ route('login') }}">Connexion</a>
                    <a href="{{ route('register') }}">Inscription</a>
                @endauth
            </div>
        </nav>

        @if(session('success'))
            <div style="background:var(--surface); border:1px solid var(--ok); color:var(--ok); padding:12px; border-radius:10px; margin-bottom:20px">
                {{ session('success') }}
            </div>
        @endif

        @isset($settings['message_global'])
            <div style="background:var(--surface); border:1px solid var(--accent); padding:12px; border-radius:10px; margin-bottom:20px">
                {{ $settings['message_global'] }}
            </div>
        @endisset

        <header>
            <span class="eyebrow">Abidjan · Transport collectif</span>
            <h1>Réservez votre place dans le véhicule</h1>
            <p>Choisissez votre zone, le nombre de places et payez en ligne. Après le paiement, téléversez la capture d'écran de votre reçu Wave pour confirmer votre place.</p>
        </header>

        <div class="grid">
            <main>
                <form id="reservation-form" class="panel" novalidate>
                    @csrf
                    <fieldset>
                        <legend><span class="n">1</span> Vos informations</legend>
                       <div class="row">
    <div class="field">
        <label>Nom</label>
        <input type="text" name="nom" value="{{ auth()->user()->name ?? '' }}" readonly @guest disabled @endguest>
        <div class="msg"></div>
    </div>
    <div class="field">
        <label>Prénom</label>
        <input type="text" name="prenom" value="{{ auth()->user()->name ?? '' }}" readonly @guest disabled @endguest>
        <div class="msg"></div>
    </div>
</div>
                    </fieldset>

                    <fieldset>
                        <legend><span class="n">2</span> Votre trajet</legend>
                        <div class="field">
                            <label>Nombre de places</label>
                            <div class="stepper">
                                <button type="button" id="btn-minus">−</button>
                                <output id="nb-places">1</output>
                                <button type="button" id="btn-plus">+</button>
                                <input type="hidden" name="places" id="input-places" value="1">
                            </div>
                            <span class="hint" style="font-size:13px; color:var(--muted)">Maximum {{ $settings['max_places'] ?? 8 }} places.</span>
                        </div>
                        <div class="field">
                            <label>Zone de préférence</label>
                            <div class="opts" id="zones-list">
                                @foreach($zones as $zone)
                                    <label class="opt">
                                        <input type="radio" name="zone_id" value="{{ $zone->id }}" {{ $loop->first ? 'checked' : '' }}>
                                        <span>
                                            <b>{{ $zone->nom }}</b>
                                            <small>{{ number_format($zone->prix, 0, ',', ' ') }} FCFA / place</small>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend><span class="n">3</span> Paiement</legend>
                        <div class="opts">
                            <label class="opt">
                                <input type="radio" name="payment_method" value="wave" checked>
                                <span><b>Wave</b><small>Paiement mobile sécurisé</small></span>
                            </label>
                        </div>
                    </fieldset>

                    <button class="cta" id="submit-btn" type="submit">
                        <span>Payer et réserver</span>
                        <span class="amt" id="total-amt">0 FCFA</span>
                    </button>
                </form>
            </main>

            <aside>
                <div class="ticket">
                    <div class="top">
                        <div class="tag">Billet · 1 véhicule</div>
                        <div class="zone" id="ticket-zone">---</div>
                        <div class="who" id="ticket-who">Passager : {{ auth()->user()->name ?? 'à renseigner' }}</div>
                    </div>
                    <div class="perf"></div>
                    <div class="bot">
                        <div class="line"><span>Prix par place</span><span id="ticket-unit">0 FCFA</span></div>
                        <div class="line"><span>Places</span><span id="ticket-nb">1</span></div>
                        <div class="total"><span>Total</span><span id="ticket-total">0 FCFA</span></div>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <script>
        const form = document.getElementById('reservation-form');
        const btnMinus = document.getElementById('btn-minus');
        const btnPlus = document.getElementById('btn-plus');
        const outputNb = document.getElementById('nb-places');
        const inputPlaces = document.getElementById('input-places');
        const totalAmt = document.getElementById('total-amt');
        const ticketZone = document.getElementById('ticket-zone');
        const ticketUnit = document.getElementById('ticket-unit');
        const ticketNb = document.getElementById('ticket-nb');
        const ticketTotal = document.getElementById('ticket-total');

        const zones = @json($zones);
        const maxPlaces = {{ $settings['max_places'] ?? 8 }};

        function updateSummary() {
            const selectedZoneId = form.querySelector('input[name="zone_id"]:checked')?.value;
            const zone = zones.find(z => z.id == selectedZoneId);
            const nb = parseInt(inputPlaces.value);

            if (zone) {
                const total = zone.prix * nb;
                ticketZone.textContent = zone.nom;
                ticketUnit.textContent = new Intl.NumberFormat('fr-FR').format(zone.prix) + ' FCFA';
                ticketNb.textContent = nb;
                ticketTotal.textContent = new Intl.NumberFormat('fr-FR').format(total) + ' FCFA';
                totalAmt.textContent = new Intl.NumberFormat('fr-FR').format(total) + ' FCFA';
            }
        }

        btnMinus.onclick = () => {
            let val = parseInt(inputPlaces.value);
            if (val > 1) {
                val--;
                inputPlaces.value = val;
                outputNb.textContent = val;
                updateSummary();
            }
        };

        btnPlus.onclick = () => {
            let val = parseInt(inputPlaces.value);
            if (val < maxPlaces) {
                val++;
                inputPlaces.value = val;
                outputNb.textContent = val;
                updateSummary();
            }
        };

        form.addEventListener('change', updateSummary);
        window.addEventListener('DOMContentLoaded', updateSummary);

        form.onsubmit = async (e) => {
            e.preventDefault();

            const btn = document.getElementById('submit-btn');
            btn.disabled = true;
            btn.innerText = 'Traitement...';

            const formData = new FormData(form);
            try {
                const response = await fetch("{{ route('reservations.store') }}", {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                if (response.status === 401 || response.status === 419) {
                    alert('Votre session a expiré ou vous n\'êtes pas connecté. Redirection vers la connexion...');
                    window.location.href = "{{ route('login') }}";
                    return;
                }

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.message || 'Une erreur serveur est survenue');
                }

                const data = await response.json();

                if (data.success) {
                    window.location.href = data.payment_url;
                } else {
                    throw new Error(data.message || 'La réservation a échoué');
                }
            } catch (error) {
                alert(error.message);
                btn.disabled = false;
                btn.innerText = 'Payer et réserver';
            }
        };
    </script>
</body>
</html>
