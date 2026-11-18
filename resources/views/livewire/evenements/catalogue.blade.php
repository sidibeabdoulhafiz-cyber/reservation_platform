<div class="home">
    @php $enCours = $this->enCours; @endphp

    {{-- Bandeau d'accueil --}}
    <section class="bleed hero-home">
        <div class="in">
            <span class="bar" aria-hidden="true"></span>
            <h1>Zéro stress, zéro retard.</h1>
            <p>Réserve ton aller-retour vers ton concert, ton festival ou ton match. Tu paies une seule fois, tu reçois ton billet QR.</p>
            <div class="hero-ctas">
                <a href="#evenements" class="btn">Voir les événements</a>
                <a href="#comment-ca-marche" class="btn btn-outline-light">Comment ça marche ?</a>
            </div>
            <ul class="hero-points">
                <li>Un seul paiement</li>
                <li>Billet QR par passager</li>
                <li>Aller et retour inclus</li>
            </ul>
        </div>
    </section>

    @if ($enCours)
        <div class="alert {{ $enCours->statut === \App\Enums\ReservationStatus::APayer ? 'alert-warn' : '' }} encours" role="status">
            <div>
                @if ($enCours->statut === \App\Enums\ReservationStatus::APayer)
                    <strong>Réservation à finaliser :</strong> {{ $enCours->libelleOffre() }}.
                    <a href="{{ route('reservations.show', $enCours) }}">Payer maintenant →</a>
                @else
                    <strong>Paiement en vérification :</strong> {{ $enCours->libelleOffre() }}.
                    <a href="{{ route('reservations.show', $enCours) }}">Suivre →</a>
                @endif
            </div>
        </div>
    @endif

    {{-- Événements --}}
    <section id="evenements" class="sec">
        <h2 class="sec-title">Prochains événements</h2>

        @if ($this->evenements->isEmpty())
            <div class="panel empty">
                <h3>Aucun événement pour le moment</h3>
                <p class="hint">Les prochains départs seront publiés ici. Reviens bientôt !</p>
            </div>
        @else
            <div class="cards">
                @foreach ($this->evenements as $evt)
                    @php
                        $prix = $evt->prixMin();
                        $complet = $evt->departs->isNotEmpty()
                            && $evt->departs->every(fn ($d) => $d->placesRestantes() === 0)
                            && $evt->typesBillets->filter->enVente()->every(fn ($t) => $t->placesRestantes() === 0);
                    @endphp
                    <a class="card evt-card" href="{{ route('evenements.show', $evt) }}" wire:key="evt-{{ $evt->id }}">
                        <x-evenement-cover :evenement="$evt" :badge="false" :eager="$loop->first" />
                        <div class="content">
                            <div class="evt-date">{{ $evt->debut_at->locale('fr')->translatedFormat('D j M Y · H\hi') }}</div>
                            <h3>{{ $evt->titre }}</h3>
                            <div class="evt-lieu">{{ $evt->lieu }}</div>
                            <div class="foot">
                                @if ($complet)
                                    <div class="from"><span>Aller-retour</span><b>Complet</b></div>
                                @elseif ($prix)
                                    <div class="from"><span>Aller-retour dès</span><b>{{ number_format($prix, 0, ',', ' ') }} FCFA</b></div>
                                @else
                                    <div class="from"><span>Aller-retour</span><b>Bientôt en vente</b></div>
                                @endif
                                <span class="go" aria-hidden="true">&gt;&gt;&gt;</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Comment ça marche --}}
    <section id="comment-ca-marche" class="bleed sec-white">
        <div class="in">
            <h2 class="sec-title">Comment ça marche ?</h2>
            <ol class="steps">
                <li>
                    <span class="n">1</span>
                    <h3>Choisis ton événement</h3>
                    <p>Sélectionne ton départ et l’arrêt le plus proche de chez toi : point principal ou quartier sur le trajet.</p>
                </li>
                <li>
                    <span class="n">2</span>
                    <h3>Paie en Mobile Money</h3>
                    <p>Un seul paiement pour l’aller et le retour. Tes places sont bloquées le temps de payer.</p>
                </li>
                <li>
                    <span class="n">3</span>
                    <h3>Reçois ton billet QR</h3>
                    <p>Un QR code par passager, par e-mail et dans « Mes réservations ». Montre-le à l’agent en montant.</p>
                </li>
            </ol>
        </div>
    </section>

    {{-- À propos --}}
    <section id="a-propos" class="sec about">
        <div>
            <h2 class="sec-title">À propos de CotaTrans</h2>
            <p>CotaTrans (Cotisation + Transport) organise le transport aller-retour des participants aux concerts, festivals, journées rétro et matchs.</p>
            <p>Fini les galères de taxi à la sortie : on s’occupe du car, des horaires et des arrêts dans les quartiers. Toi, tu profites de l’événement.</p>
        </div>
        <ul class="about-points">
            <li><b>Ramassage près de chez toi</b><span>Point de rassemblement principal et arrêts intermédiaires sur l’itinéraire.</span></li>
            <li><b>Paiement sécurisé</b><span>Wave et Mobile Money, sans espèces à bord.</span></li>
            <li><b>Organisateur ?</b><span>Tu veux proposer le transport pour ton événement ? Contacte l’équipe CotaTrans.</span></li>
        </ul>
    </section>

    <style>
        main { overflow-x: clip; }
        .home .wrap, .wrap:has(.home) { padding-top: 0; }
        .bleed { margin-left: calc(50% - 50vw); margin-right: calc(50% - 50vw); }
        .bleed > .in { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .hero-home { background: var(--brand); color: #fff; padding: 80px 0 72px; margin-bottom: 40px; }
        .hero-home .bar { display: block; width: 78px; height: 5px; background: var(--accent); border-radius: 3px; margin-bottom: 36px; }
        .hero-home h1 { font: 800 clamp(2.4rem, 6.5vw, 4.2rem)/1.05 var(--display); letter-spacing: -.02em; margin: 0 0 22px; }
        .hero-home p { font-size: clamp(1.05rem, 2.2vw, 1.4rem); line-height: 1.6; max-width: 780px; margin: 0 0 32px; color: #e8eef6; }
        .hero-ctas { display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 30px; }
        .hero-ctas .btn { font-size: 1.15rem; padding: 14px 32px; min-height: 60px; border-radius: 14px; }
        .btn-outline-light { background: transparent; border: 1.5px solid rgba(255,255,255,.75); color: #fff; }
        .btn-outline-light:hover { background: rgba(255,255,255,.1); border-color: #fff; }
        .hero-points { list-style: none; padding: 0; margin: 0; display: flex; flex-wrap: wrap; gap: 10px 34px; font-size: 1.05rem; font-weight: 500; }
        .encours { margin-bottom: 24px; }
        .sec { padding: 8px 0 56px; }
        .sec-title { font: 700 clamp(1.8rem, 4vw, 2.5rem)/1.15 var(--display); color: var(--brand); margin: 0 0 40px; position: relative; padding-bottom: 18px; letter-spacing: -.01em; }
        .sec-title::after { content: ''; position: absolute; left: 0; bottom: 0; width: 66px; height: 5px; border-radius: 3px; background: var(--accent); }
        .home .cards { grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 32px; }
        .evt-card { border-radius: 22px; border: 1px solid var(--line); box-shadow: 0 2px 6px rgba(12, 65, 115, .05); }
        .evt-card .content { padding: 26px 32px 30px; gap: 0; }
        .evt-date { color: var(--brand); font-weight: 500; font-size: 1.05rem; margin-bottom: 18px; }
        .evt-card h3 { font: 600 1.6rem/1.2 var(--display); margin: 0 0 8px; color: var(--fg); }
        .evt-lieu { color: var(--muted); font-size: 1.1rem; }
        .evt-card .foot { border-top: 1px solid var(--line); margin-top: 34px; padding-top: 22px; align-items: flex-end; }
        .evt-card .from { display: grid; gap: 4px; font-size: 1.05rem; color: var(--muted); }
        .evt-card .from b { font: 700 1.55rem var(--display); color: var(--brand); }
        .evt-card .go { font: 600 1.25rem var(--display); color: var(--brand); letter-spacing: -.05em; transition: transform .15s, color .15s; }
        .evt-card:hover .go { transform: translateX(4px); color: var(--accent); }
        .sec-white { background: #fff; padding: 64px 0; border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); margin-bottom: 56px; }
        .steps { list-style: none; padding: 0; margin: 0; display: grid; gap: 24px; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); }
        .steps li { background: var(--bg); border-radius: 22px; padding: 28px; border: 1px solid var(--line); }
        .steps .n { display: grid; place-items: center; width: 48px; height: 48px; border-radius: 50%; background: var(--accent); color: #fff; font: 800 1.3rem var(--display); margin-bottom: 16px; }
        .steps h3 { color: var(--brand); font: 700 1.3rem var(--display); margin: 0 0 8px; }
        .steps p { margin: 0; color: var(--muted); font-size: 1.05rem; }
        .about { display: grid; gap: 32px; }
        @media (min-width: 900px) { .about { grid-template-columns: 1.1fr 1fr; align-items: start; } }
        .about p { font-size: 1.1rem; color: var(--muted); margin: 0 0 14px; max-width: 60ch; }
        .about-points { list-style: none; margin: 0; padding: 0; display: grid; gap: 14px; }
        .about-points li { background: #fff; border: 1px solid var(--line); border-left: 5px solid var(--accent); border-radius: 16px; padding: 16px 20px; display: grid; gap: 4px; }
        .about-points b { color: var(--brand); font-size: 1.1rem; }
        .about-points span { color: var(--muted); }
        .empty { text-align: center; padding: 40px 20px; }
        @media (max-width: 600px) { .hero-home { padding: 52px 0 48px; } .evt-card .content { padding: 22px 22px 24px; } }
    </style>
</div>
