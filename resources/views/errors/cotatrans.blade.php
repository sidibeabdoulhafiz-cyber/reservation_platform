{{-- Page d'erreur autonome (sans base de données) aux couleurs CotaTrans. --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#0C4173">
    <title>{{ $titre }} · CotaTrans</title>
    <link rel="icon" href="{{ asset('images/logo-icone.svg') }}" type="image/svg+xml">
    <style>
        *{box-sizing:border-box} body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;background:#0C4173;color:#fff;text-align:center}
        .box{max-width:460px} img{height:54px;margin-bottom:28px} .code{font-size:clamp(4rem,18vw,7rem);font-weight:800;line-height:1;color:#FB7212;letter-spacing:-.04em}
        h1{font-size:1.6rem;margin:10px 0 8px} p{color:#d5e1ee;line-height:1.55;margin:0 0 26px}
        .acts{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}
        a,button{display:inline-flex;align-items:center;min-height:48px;padding:0 22px;border-radius:12px;font:700 16px inherit;text-decoration:none;cursor:pointer;border:0}
        .p{background:#FB7212;color:#fff} .s{background:rgba(255,255,255,.12);color:#fff}
    </style>
</head>
<body>
    <main class="box">
        <img src="{{ asset('images/logo-horizontal-blanc.svg') }}" alt="CotaTrans">
        <div class="code">{{ $code }}</div>
        <h1>{{ $titre }}</h1>
        <p>{{ $texte }}</p>
        <div class="acts">
            @if (! empty($recharger))
                <button class="p" onclick="location.reload()">Recharger la page</button>
            @endif
            <a class="{{ empty($recharger) ? 'p' : 's' }}" href="{{ url('/') }}">Voir les événements</a>
            <a class="s" href="{{ url('/contact') }}">Nous contacter</a>
        </div>
    </main>
</body>
</html>
