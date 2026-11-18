<div class="home">
    @php
        $enCours = $this->enCours;
        $hero = $this->heroImage;
        $nb = $this->evenements->count();
    @endphp

    {{-- 1. Bannière principale --}}
    <section class="bleed hero-home {{ $hero ? 'has-photo' : '' }}" @if ($hero) style="--hero: url('{{ $hero }}')" @endif>
        @unless ($hero)
            <svg class="hero-illu" viewBox="0 0 600 420" aria-hidden="true">
                <path d="M-20 360 C 120 300, 200 380, 320 300 S 520 200, 640 230" fill="none" stroke="rgba(255,255,255,.14)" stroke-width="46" stroke-linecap="round"/>
                <path d="M-20 360 C 120 300, 200 380, 320 300 S 520 200, 640 230" fill="none" stroke="rgba(255,255,255,.35)" stroke-width="3" stroke-dasharray="14 16"/>
                <g transform="translate(250 170)">
                    <rect x="0" y="0" width="220" height="104" rx="22" fill="#FB7212"/>
                    <rect x="16" y="16" width="44" height="34" rx="6" fill="#fff" opacity=".92"/>
                    <rect x="68" y="16" width="44" height="34" rx="6" fill="#fff" opacity=".92"/>
                    <rect x="120" y="16" width="44" height="34" rx="6" fill="#fff" opacity=".92"/>
                    <rect x="172" y="16" width="34" height="56" rx="6" fill="#fff" opacity=".92"/>
                    <rect x="0" y="62" width="220" height="8" fill="#e0600a"/>
                    <circle cx="52" cy="106" r="18" fill="#08305a"/><circle cx="52" cy="106" r="7" fill="#c3d2e4"/>
                    <circle cx="170" cy="106" r="18" fill="#08305a"/><circle cx="170" cy="106" r="7" fill="#c3d2e4"/>
                </g>
                <g fill="#fff" opacity=".9">
                    <path d="M512 92s-26-23-26-43a26 26 0 0 1 52 0c0 20-26 43-26 43z"/>
                </g>
                <circle cx="512" cy="50" r="9" fill="#0C4173"/>
            </svg>
        @endunless

        <div class="in">
            <div class="hero-text">
                <h1>Zéro stress, zéro retard.</h1>
                <p>Réserve ton aller-retour en car vers ton concert, ton festival ou ton match. Ramassage près de chez toi, un billet QR par passager.</p>

                <form class="search" role="search" wire:submit="$refresh" onsubmit="document.getElementById('evenements').scrollIntoView()">
                    <label for="q" class="sr">Rechercher un événement</label>
                    <x-ico name="search" size="22" class="search-ico" />
                    <input id="q" type="search" wire:model.live.debounce.400ms="q" placeholder="Concert, festival, match, lieu…" autocomplete="off" enterkeyhint="search">
                    <button type="submit" class="btn">Rechercher</button>
                </form>

                <ul class="hero-points">
                    <li><x-ico name="check-circle" size="18" /> Aller et retour inclus</li>
                    <li><x-ico name="check-circle" size="18" /> Paiement Mobile Money</li>
                    <li><x-ico name="check-circle" size="18" /> Billet QR par passager</li>
                </ul>
            </div>
        </div>
    </section>

    @if ($enCours)
        <a class="resume-card" href="{{ route('reservations.show', $enCours) }}">
            <span class="rc-ico {{ $enCours->statut === \App\Enums\ReservationStatus::APayer ? 'warn' : '' }}"><x-ico name="{{ $enCours->statut === \App\Enums\ReservationStatus::APayer ? 'wallet' : 'clock' }}" size="22" /></span>
            <span class="rc-txt">
                <b>{{ $enCours->statut === \App\Enums\ReservationStatus::APayer ? 'Réservation à finaliser' : 'Paiement en vérification' }}</b>
                <small>{{ $enCours->libelleOffre() }}@if ($enCours->statut === \App\Enums\ReservationStatus::APayer && $enCours->expire_at) · avant {{ $enCours->expire_at->format('H\hi') }}@endif</small>
            </span>
            <span class="rc-go">{{ $enCours->statut === \App\Enums\ReservationStatus::APayer ? 'Payer' : 'Suivre' }} <x-ico name="arrow-right" size="18" /></span>
        </a>
    @endif

    {{-- 2 & 3. Découverte et événements disponibles --}}
    <section id="evenements" class="sec">
        <div class="sec-head">
            <div>
                <h2 class="sec-title">Événements disponibles</h2>
                <p class="sec-lead" style="margin:0">Choisis ton événement, on s’occupe du trajet.</p>
            </div>
            <div class="filters" role="group" aria-label="Filtrer par date">
                @foreach (['tout' => 'Tous', 'semaine' => 'Cette semaine', 'mois' => 'Ce mois-ci'] as $val => $lib)
                    <button type="button" class="pill-btn {{ $quand === $val ? 'on' : '' }}" wire:click="$set('quand', '{{ $val }}')" aria-pressed="{{ $quand === $val ? 'true' : 'false' }}">{{ $lib }}</button>
                @endforeach
            </div>
        </div>

        @if ($q !== '' || $quand !== 'tout')
            <p class="result-line" aria-live="polite">
                {{ $nb }} résultat{{ $nb > 1 ? 's' : '' }}@if ($q !== '') pour « {{ $q }} »@endif
                · <button type="button" class="linklike" wire:click="effacer">Effacer les filtres</button>
            </p>
        @endif

        <div wire:loading.class="loading" wire:target="q,quand,effacer">
            @if ($this->evenements->isEmpty())
                <div class="panel empty">
                    <x-ico name="calendar" size="34" />
                    <h3>{{ $q !== '' || $quand !== 'tout' ? 'Aucun événement ne correspond' : 'Aucun événement pour le moment' }}</h3>
                    <p class="hint">{{ $q !== '' || $quand !== 'tout' ? 'Essaie un autre mot-clé ou une autre période.' : 'Les prochains départs seront publiés ici très bientôt.' }}</p>
                    @if ($q !== '' || $quand !== 'tout')
                        <button type="button" class="btn btn-ghost" style="margin-top:12px" wire:click="effacer">Voir tous les événements</button>
                    @endif
                </div>
            @else
                <div class="cards">
                    @foreach ($this->evenements as $evt)
                        @php
                            $prix = $evt->prixMin();
                            $places = $evt->departs->sum(fn ($d) => $d->placesRestantes());
                            $complet = $evt->departs->isNotEmpty() && $places === 0
                                && $evt->typesBillets->filter->enVente()->every(fn ($t) => $t->placesRestantes() === 0);
                            $nbArrets = $evt->departs->flatMap->tarifs->pluck('zone_id')->unique()->count();
                        @endphp
                        <a class="card evt-card reveal" href="{{ route('evenements.show', $evt) }}" wire:key="evt-{{ $evt->id }}">
                            <div class="cover-wrap">
                                <x-evenement-cover :evenement="$evt" :badge="true" :eager="$loop->first" />
                                @if ($complet)
                                    <span class="flag flag-off">Complet</span>
                                @elseif ($evt->departs->isNotEmpty() && $places <= 10)
                                    <span class="flag flag-hot">Plus que {{ $places }} place{{ $places > 1 ? 's' : '' }}</span>
                                @endif
                            </div>
                            <div class="content">
                                <h3>{{ $evt->titre }}</h3>
                                <ul class="evt-meta">
                                    <li><x-ico name="calendar" size="16" /> {{ $evt->debut_at->locale('fr')->translatedFormat('l j F · H\hi') }}</li>
                                    <li><x-ico name="pin" size="16" /> {{ $evt->lieu }}, {{ $evt->ville }}</li>
                                    @if ($nbArrets > 0)
                                        <li><x-ico name="route" size="16" /> {{ $nbArrets }} arrêt{{ $nbArrets > 1 ? 's' : '' }} de ramassage</li>
                                    @endif
                                </ul>
                                <div class="foot">
                                    <div class="from">
                                        @if ($complet)
                                            <span>Aller-retour</span><b>Complet</b>
                                        @elseif ($prix)
                                            <span>Aller-retour dès</span><b>{{ number_format($prix, 0, ',', ' ') }} FCFA</b>
                                        @else
                                            <span>Aller-retour</span><b>Bientôt</b>
                                        @endif
                                    </div>
                                    <span class="go">{{ $complet ? 'Voir' : 'Réserver' }} <x-ico name="arrow-right" size="18" /></span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- 4. Fonctionnement --}}
    <section id="comment-ca-marche" class="bleed sec-white">
        <div class="in">
            <h2 class="sec-title">Comment ça marche ?</h2>
            <p class="sec-lead">Quatre étapes, deux minutes. Aucun appel, aucune file d’attente.</p>
            <ol class="steps">
                <li class="reveal"><span class="n">1</span><x-ico name="search" size="26" /><h3>Choisis ton événement</h3><p>Trouve ton concert, ton festival ou ton match dans la liste.</p></li>
                <li class="reveal"><span class="n">2</span><x-ico name="pin" size="26" /><h3>Ton arrêt, tes passagers</h3><p>Sélectionne l’arrêt le plus proche de chez toi et ajoute tes amis, même sans compte.</p></li>
                <li class="reveal"><span class="n">3</span><x-ico name="wallet" size="26" /><h3>Paie en Mobile Money</h3><p>Un seul paiement pour tout le groupe, l’aller et le retour.</p></li>
                <li class="reveal"><span class="n">4</span><x-ico name="qr" size="26" /><h3>Monte à bord</h3><p>Chaque passager reçoit son QR code, contrôlé à l’aller et au retour.</p></li>
            </ol>
        </div>
    </section>

    {{-- 5. Avantages --}}
    <section id="avantages" class="sec">
        <h2 class="sec-title">Pourquoi CotaTrans ?</h2>
        <p class="sec-lead">Tu profites de l’événement, on gère le trajet.</p>
        <div class="benefits">
            <div class="benefit reveal"><span class="b-ico"><x-ico name="route" size="24" /></span><h3>Ramassage près de chez toi</h3><p>Un point de rassemblement principal et des arrêts dans les quartiers traversés, avec l’heure de passage.</p></div>
            <div class="benefit reveal"><span class="b-ico"><x-ico name="bus" size="24" /></span><h3>Retour garanti</h3><p>Fini la chasse au taxi à la sortie : ton car te ramène après l’événement.</p></div>
            <div class="benefit reveal"><span class="b-ico"><x-ico name="users" size="24" /></span><h3>Pratique en groupe</h3><p>Réserve pour tes amis en une fois. Chacun reçoit son propre billet.</p></div>
            <div class="benefit reveal"><span class="b-ico"><x-ico name="ticket" size="24" /></span><h3>Prix fixe, sans surprise</h3><p>Le prix affiché comprend l’aller et le retour. Rien à payer à bord.</p></div>
        </div>
    </section>

    {{-- 6. Sécurité & confiance --}}
    <section class="bleed trust">
        <div class="in">
            <div class="trust-head">
                <h2>Voyage en toute confiance</h2>
                <p>Chaque étape est contrôlée, du paiement à la montée dans le car.</p>
            </div>
            <ul class="trust-list">
                <li class="reveal"><x-ico name="shield" size="26" /><div><b>Paiements vérifiés</b><span>Chaque paiement est contrôlé par notre équipe avant l’émission des billets.</span></div></li>
                <li class="reveal"><x-ico name="qr" size="26" /><div><b>Billets infalsifiables</b><span>Un QR code unique par passager, scanné à la montée de l’aller et du retour.</span></div></li>
                <li class="reveal"><x-ico name="users" size="26" /><div><b>Liste des passagers à bord</b><span>L’agent connaît les passagers attendus à chaque arrêt.</span></div></li>
                <li class="reveal"><x-ico name="headset" size="26" /><div><b>Une équipe joignable</b><span>Une question avant ou pendant le trajet ? On te répond.</span></div></li>
            </ul>
        </div>
    </section>

    {{-- 7. Appel à l'action --}}
    <section class="cta-band reveal">
        <div>
            <h2>Prêt pour ton prochain événement ?</h2>
            <p>{{ auth()->check() ? 'Réserve ta place maintenant : les cars se remplissent vite.' : 'Crée ton compte en 30 secondes et réserve ta place, ou celles de tes amis.' }}</p>
        </div>
        <div class="cta-actions">
            @guest
                <a href="{{ route('register') }}" class="btn btn-lg">Créer mon compte</a>
            @endguest
            <a href="#evenements" class="btn btn-lg {{ auth()->check() ? '' : 'btn-ghost' }}">Voir les événements</a>
        </div>
    </section>

    <style>
        .wrap:has(.home) { padding-top: 0; }

        /* Bannière */
        .hero-home { position: relative; background: var(--brand); color: #fff; overflow: hidden; }
        .hero-home.has-photo { background: linear-gradient(90deg, rgba(8,48,90,.96) 0%, rgba(12,65,115,.88) 38%, rgba(12,65,115,.35) 70%, rgba(12,65,115,.05) 100%), var(--hero) center / cover no-repeat, var(--brand); }
        .hero-home .in { position: relative; z-index: 1; padding-top: 88px; padding-bottom: 96px; }
        .hero-text { max-width: 640px; }
        .hero-home h1 { font: 800 clamp(2.3rem, 6.4vw, 4rem)/1.04 var(--display); letter-spacing: -.025em; margin: 0 0 18px; }
        .hero-home p { font-size: clamp(1.05rem, 2.2vw, 1.25rem); line-height: 1.55; color: #e3ebf5; margin: 0 0 28px; max-width: 54ch; }
        .hero-illu { position: absolute; right: -40px; bottom: 0; width: min(56vw, 640px); height: auto; opacity: .95; }
        @media (max-width: 860px) { .hero-illu { opacity: .22; width: 120vw; right: -30vw; } }
        .search { display: flex; align-items: center; gap: 8px; background: #fff; border-radius: 16px; padding: 7px 7px 7px 16px; box-shadow: 0 16px 40px rgba(4, 25, 50, .3); max-width: 560px; }
        .search .search-ico { color: var(--muted); }
        .search input { flex: 1; min-width: 0; border: 0; outline: none; font: 400 17px var(--body); color: var(--fg); padding: 12px 4px; background: transparent; }
        .search input::-webkit-search-cancel-button { cursor: pointer; }
        .search .btn { min-height: 50px; border-radius: 12px; }
        .search:focus-within { box-shadow: 0 0 0 4px rgba(251,114,18,.35), 0 16px 40px rgba(4, 25, 50, .3); }
        .hero-points { list-style: none; padding: 0; margin: 24px 0 0; display: flex; flex-wrap: wrap; gap: 10px 24px; font-weight: 500; color: #e3ebf5; }
        .hero-points li { display: inline-flex; align-items: center; gap: 8px; }
        .hero-points .ico { color: var(--accent); }
        @media (max-width: 600px) {
            .hero-home .in { padding-top: 44px; padding-bottom: 52px; }
            .search { padding-left: 12px; }
            .search .btn { padding: 0 16px; }
            .search .btn { font-size: 0; gap: 0; width: 50px; }
            .search .btn::before { content: ''; width: 20px; height: 20px; background: currentColor; -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.4' stroke-linecap='round'%3E%3Cpath d='M5 12h14M13 6l6 6-6 6'/%3E%3C/svg%3E") center / contain no-repeat; mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.4' stroke-linecap='round'%3E%3Cpath d='M5 12h14M13 6l6 6-6 6'/%3E%3C/svg%3E") center / contain no-repeat; }
        }

        /* Reprise de réservation */
        .resume-card { display: flex; align-items: center; gap: 14px; background: #fff; border: 1px solid var(--line); border-radius: var(--r); padding: 14px 16px; margin: -28px auto 0; position: relative; z-index: 2; box-shadow: var(--shadow-lg); text-decoration: none; max-width: 760px; }
        .rc-ico { width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center; background: var(--brand-soft); color: var(--brand); flex: none; }
        .rc-ico.warn { background: var(--warn-soft); color: var(--warn); }
        .rc-txt { flex: 1; min-width: 0; display: grid; }
        .rc-txt small { color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .rc-go { display: inline-flex; align-items: center; gap: 6px; font-weight: 600; color: var(--accent-dark); }

        /* Sections */
        .sec { padding: 64px 0 24px; }
        .sec-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-end; gap: 16px; margin-bottom: 28px; }
        .filters { display: flex; gap: 8px; overflow-x: auto; scrollbar-width: none; max-width: 100%; }
        .filters::-webkit-scrollbar { display: none; }
        .result-line { color: var(--muted); margin: -12px 0 20px; }
        .loading { opacity: .5; transition: opacity .15s; }

        /* Cartes événement */
        .evt-card .cover-wrap { position: relative; }
        .flag { position: absolute; top: 12px; right: 12px; font: 600 12.5px var(--body); padding: 5px 10px; border-radius: 99px; }
        .flag-hot { background: var(--accent); color: #fff; }
        .flag-off { background: rgba(15,27,45,.8); color: #fff; }
        .evt-card .content { padding: 20px 22px 22px; display: flex; flex-direction: column; flex: 1; }
        .evt-card h3 { font: 600 1.3rem/1.25 var(--display); margin: 0 0 12px; color: var(--fg); }
        .evt-meta { list-style: none; margin: 0; padding: 0; display: grid; gap: 7px; color: var(--muted); font-size: 15px; }
        .evt-meta li { display: flex; align-items: center; gap: 8px; }
        .evt-meta .ico { color: var(--brand); }
        .evt-meta li:first-child { text-transform: capitalize; }
        .evt-card .foot { border-top: 1px solid var(--line); margin-top: auto; padding-top: 16px; display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; }
        .evt-card .content > .evt-meta { margin-bottom: 20px; }
        .evt-card .from { display: grid; gap: 1px; font-size: 14px; color: var(--muted); }
        .evt-card .from b { font: 700 1.35rem var(--display); color: var(--brand); }
        .evt-card .go { display: inline-flex; align-items: center; gap: 6px; font-weight: 600; color: #fff; background: var(--accent); padding: 10px 16px; border-radius: 12px; transition: background .15s; }
        .evt-card:hover .go { background: var(--accent-dark); }

        /* Étapes */
        .sec-white { background: #fff; padding: 72px 0; border-block: 1px solid var(--line); margin-top: 56px; }
        .steps { list-style: none; padding: 0; margin: 0; display: grid; gap: 18px; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); counter-reset: s; }
        .steps li { position: relative; background: var(--bg); border-radius: var(--r-lg); padding: 26px 22px 22px; border: 1px solid var(--line); }
        .steps .n { position: absolute; top: 18px; right: 20px; font: 800 2.2rem var(--display); color: var(--brand-soft); line-height: 1; }
        .steps .ico { color: var(--accent); margin-bottom: 14px; }
        .steps h3 { color: var(--brand); font-size: 1.15rem; margin: 0 0 6px; }
        .steps p { margin: 0; color: var(--muted); }

        /* Avantages */
        .benefits { display: grid; gap: 18px; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
        .benefit { background: #fff; border: 1px solid var(--line); border-radius: var(--r-lg); padding: 24px 22px; }
        .b-ico { width: 48px; height: 48px; border-radius: 14px; background: var(--accent-soft); color: var(--accent-dark); display: grid; place-items: center; margin-bottom: 14px; }
        .benefit h3 { color: var(--fg); margin-bottom: 6px; }
        .benefit p { color: var(--muted); margin: 0; }

        /* Confiance */
        .trust { background: var(--brand); color: #fff; padding: 72px 0; margin-top: 64px; }
        .trust-head { max-width: 640px; margin-bottom: 32px; }
        .trust h2 { font: 700 clamp(1.55rem, 3.6vw, 2.2rem) var(--display); margin: 0 0 8px; }
        .trust-head p { color: #c9d8ea; font-size: 1.08rem; margin: 0; }
        .trust-list { list-style: none; margin: 0; padding: 0; display: grid; gap: 16px; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); }
        .trust-list li { display: flex; gap: 14px; align-items: flex-start; background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.12); border-radius: var(--r); padding: 20px; }
        .trust-list .ico { color: var(--accent); margin-top: 2px; }
        .trust-list b { display: block; font-size: 1.05rem; margin-bottom: 4px; }
        .trust-list span { color: #c9d8ea; font-size: 15px; }

        /* CTA final */
        .cta-band { margin: 64px 0 8px; background: #fff; border: 1px solid var(--line); border-left: 6px solid var(--accent); border-radius: var(--r-lg); padding: 32px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 20px; box-shadow: var(--shadow); }
        .cta-band h2 { color: var(--brand); margin: 0 0 6px; }
        .cta-band p { color: var(--muted); margin: 0; max-width: 52ch; }
        .cta-actions { display: flex; gap: 12px; flex-wrap: wrap; }
        @media (max-width: 600px) {
            .sec { padding-top: 44px; }
            .sec-white, .trust { padding: 52px 0; }
            .cta-band { padding: 24px 20px; }
            .cta-actions, .cta-actions .btn { width: 100%; }
        }
    </style>
</div>
