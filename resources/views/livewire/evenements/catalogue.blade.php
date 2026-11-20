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
                <p class="hero-kicker">Transport événementiel à Abidjan</p>
                <h1>Zéro stress, zéro retard.<span class="h1-sub">Ton aller-retour en car pour tes événements et sorties.</span></h1>
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
                        <a class="card evt-card enter" style="--i: {{ min($loop->index, 8) }}" href="{{ route('evenements.show', $evt) }}" wire:key="evt-{{ $evt->id }}">
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

    {{-- 4. Fonctionnement (interactif, statique pour Livewire) --}}
    <div wire:ignore>
    <section id="comment-ca-marche" class="bleed sec-white">
        <div class="in">
            <h2 class="sec-title" data-anim>Comment ça marche ?</h2>
            <p class="sec-lead" data-anim>Quatre étapes, deux minutes. Touche une étape pour la voir en action.</p>

            <div class="how" x-data="{
                    etape: 0, total: 4, pause: false, t: null, cycle: 0,
                    init() {
                        const io = new IntersectionObserver(([e]) => { e.isIntersecting ? this.lancer() : this.arreter(); }, { threshold: .35 });
                        io.observe(this.$el);
                    },
                    lancer() { this.arreter(); this.t = setInterval(() => { if (! this.pause) this.aller((this.etape + 1) % this.total, false); }, 5000); },
                    arreter() { clearInterval(this.t); },
                    aller(i, manuel = true) { this.etape = i; this.cycle++; if (manuel) this.lancer(); }
                 }" @mouseenter="pause = true" @mouseleave="pause = false" @focusin="pause = true" @focusout="pause = false">

                <ol class="how-steps" role="tablist" aria-label="Étapes">
                    @foreach ([
                        ['search', 'Choisis ton événement', 'Concert, festival, match : cherche par nom ou par lieu, filtre par date.'],
                        ['pin', 'Ton arrêt et tes passagers', 'Sélectionne l’arrêt le plus proche et ajoute tes amis par leur nom, sans compte pour eux.'],
                        ['wallet', 'Paie en Mobile Money', 'Un seul paiement Wave pour tout le groupe. Envoie la capture, le reçu est lu automatiquement.'],
                        ['qr', 'Monte à bord', 'Chaque passager reçoit son QR code, scanné à l’aller puis au retour.'],
                    ] as $i => [$ico, $titre, $texte])
                        <li role="presentation">
                            <button type="button" role="tab" :aria-selected="etape === {{ $i }}" :class="{ on: etape === {{ $i }} }" @click="aller({{ $i }})">
                                <span class="how-n">{{ $i + 1 }}</span>
                                <span class="how-txt">
                                    <b>{{ $titre }}</b>
                                    <small>{{ $texte }}</small>
                                </span>
                                <span class="how-bar" aria-hidden="true"><template x-if="etape === {{ $i }}"><i :style="pause ? 'animation-play-state:paused' : ''"></i></template></span>
                            </button>
                        </li>
                    @endforeach
                </ol>

                <div class="phone" aria-hidden="true">
                    <div class="phone-notch"></div>
                    <div class="screen">
                        {{-- 1. Recherche --}}
                        <div class="scr" x-show="etape === 0" x-transition:enter="scr-in" x-transition:enter-start="scr-from">
                            <div class="m-search"><x-ico name="search" size="16" /> Concert…</div>
                            <div class="m-card"><div class="m-cover c1"></div><b>Concert au Plateau</b><small>Sam 12 déc · Palais de la Culture</small><div class="m-row"><span>dès <b>2 500 F</b></span><span class="m-btn">Réserver</span></div></div>
                            <div class="m-card dim"><div class="m-cover c2"></div><b>Journée Rétro</b><small>Dim 20 déc · Grand-Bassam</small></div>
                        </div>
                        {{-- 2. Arrêt + passagers --}}
                        <div class="scr" x-show="etape === 1" x-cloak x-transition:enter="scr-in" x-transition:enter-start="scr-from">
                            <p class="m-h">Où montes-tu ?</p>
                            <div class="m-stop on"><span>17h00</span><i></i><b>Yopougon</b></div>
                            <div class="m-stop"><span>17h20</span><i></i><b>Cocody</b></div>
                            <div class="m-stop"><span>17h40</span><i></i><b>Marcory</b></div>
                            <p class="m-h">Qui voyage ?</p>
                            <div class="m-input">Awa Koné <em>toi</em></div>
                            <div class="m-input">Moussa Traoré <em>invité</em></div>
                            <div class="m-add">+ Ajouter un passager</div>
                        </div>
                        {{-- 3. Paiement --}}
                        <div class="scr" x-show="etape === 2" x-cloak x-transition:enter="scr-in" x-transition:enter-start="scr-from">
                            <p class="m-h">Paiement</p>
                            <div class="m-wave">Payer <b>5 000 F</b> avec Wave</div>
                            <div class="m-receipt"><x-ico name="camera" size="18" /> recu-wave.jpg <em>✓</em></div>
                            <div class="m-read"><span>Montant</span><b>5 000 F</b></div>
                            <div class="m-read"><span>ID transaction</span><b>T_8K2LMN83PQ</b></div>
                            <div class="m-ok">Reçu lu automatiquement ✓</div>
                        </div>
                        {{-- 4. Billet --}}
                        <div class="scr" x-show="etape === 3" x-cloak x-transition:enter="scr-in" x-transition:enter-start="scr-from">
                            <div class="m-ticket">
                                <small>COTATRANS · ALLER-RETOUR</small>
                                <b>Concert au Plateau</b>
                                <div class="m-qr">@for ($k = 0; $k < 49; $k++)<i style="opacity:{{ [1,0,1,1,0,1,0,1,1,0,0,1,1,0,1,0,1,1,0,1,1,1,0,0,1,0,1,1,0,1,1,0,1,0,0,1,1,1,0,1,0,1,1,0,1,0,1,1,0][$k] }}"></i>@endfor</div>
                                <span>Awa Koné · Yopougon 17h00</span>
                                <div class="m-legs"><em class="done">Aller ✓</em><em>Retour</em></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    </div>

    <div wire:ignore>
    {{-- 5. Avantages --}}
    <section id="avantages" class="sec">
        <h2 class="sec-title" data-anim>Pourquoi CotaTrans ?</h2>
        <p class="sec-lead" data-anim>Tu profites de l’événement, on gère le trajet.</p>
        <div class="benefits">
            <div class="benefit" data-anim><span class="b-ico"><x-ico name="route" size="24" /></span><h3>Ramassage près de chez toi</h3><p>Un point de rassemblement principal et des arrêts dans les quartiers traversés, avec l’heure de passage.</p></div>
            <div class="benefit" data-anim><span class="b-ico"><x-ico name="bus" size="24" /></span><h3>Retour garanti</h3><p>Fini la chasse au taxi à la sortie : ton car te ramène après l’événement.</p></div>
            <div class="benefit" data-anim><span class="b-ico"><x-ico name="users" size="24" /></span><h3>Pratique en groupe</h3><p>Réserve pour tes amis en une fois. Chacun reçoit son propre billet.</p></div>
            <div class="benefit" data-anim><span class="b-ico"><x-ico name="ticket" size="24" /></span><h3>Prix fixe, sans surprise</h3><p>Le prix affiché comprend l’aller et le retour. Rien à payer à bord.</p></div>
        </div>
    </section>

    {{-- 6. Sécurité & confiance --}}
    <section class="bleed trust">
        <div class="in">
            <div class="trust-head" data-anim>
                <h2>Voyage en toute confiance</h2>
                <p>Chaque étape est contrôlée, du paiement à la montée dans le car.</p>
            </div>
            <ul class="trust-list">
                <li data-anim><x-ico name="shield" size="26" /><div><b>Paiements vérifiés</b><span>Chaque paiement est contrôlé par notre équipe avant l’émission des billets.</span></div></li>
                <li data-anim><x-ico name="qr" size="26" /><div><b>Billets infalsifiables</b><span>Un QR code unique par passager, scanné à la montée de l’aller et du retour.</span></div></li>
                <li data-anim><x-ico name="users" size="26" /><div><b>Liste des passagers à bord</b><span>L’agent connaît les passagers attendus à chaque arrêt.</span></div></li>
                <li data-anim><x-ico name="headset" size="26" /><div><b>Une équipe joignable</b><span>Une question avant ou pendant le trajet ? On te répond.</span></div></li>
            </ul>
        </div>
    </section>

    {{-- 7. Appel à l'action --}}
    <section class="cta-band" data-anim>
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
    </div>

    <style>
        .wrap:has(.home) { padding-top: 0; }

        /* Bannière */
        .hero-home { position: relative; background: var(--brand); color: #fff; overflow: hidden; }
        .hero-home.has-photo { background: linear-gradient(90deg, rgba(8,48,90,.96) 0%, rgba(12,65,115,.88) 38%, rgba(12,65,115,.35) 70%, rgba(12,65,115,.05) 100%), var(--hero) center / cover no-repeat, var(--brand); }
        .hero-home .in { position: relative; z-index: 1; padding-top: 88px; padding-bottom: 96px; }
        .hero-text { max-width: 640px; }
        .hero-home h1 { font: 800 clamp(2.3rem, 6.4vw, 4rem)/1.04 var(--display); letter-spacing: -.025em; margin: 0 0 18px; }
        .hero-kicker { display: inline-block; font: 600 13px var(--body); letter-spacing: .08em; text-transform: uppercase; color: #ffb07a; margin: 0 0 14px !important; }
        .h1-sub { display: block; font: 600 clamp(1.15rem, 2.6vw, 1.6rem)/1.3 var(--display); letter-spacing: 0; color: #fff; opacity: .92; margin-top: 12px; }
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

        /* Comment ça marche (interactif) */
        .how { display: grid; gap: 28px; align-items: center; }
        @media (min-width: 900px) { .how { grid-template-columns: 1.1fr .9fr; gap: 56px; } }
        .how-steps { list-style: none; margin: 0; padding: 0; display: grid; gap: 10px; }
        .how-steps button { width: 100%; text-align: left; display: grid; grid-template-columns: 44px 1fr; gap: 4px 14px; align-items: start; padding: 16px 18px; border-radius: var(--r); border: 1.5px solid var(--line); background: #fff; cursor: pointer; font: inherit; color: inherit; position: relative; overflow: hidden; transition: border-color .25s, box-shadow .25s, background .25s; }
        .how-steps button:hover { border-color: #c3cdda; }
        .how-steps button.on { border-color: var(--brand); box-shadow: var(--shadow); background: linear-gradient(90deg, var(--brand-soft), #fff 60%); }
        .how-n { width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; background: var(--brand-soft); color: var(--brand); font: 700 1.1rem var(--display); transition: background .25s, color .25s; }
        .how-steps button.on .how-n { background: var(--accent); color: #fff; }
        .how-txt b { display: block; font-size: 1.08rem; color: var(--fg); }
        .how-txt small { display: block; color: var(--muted); font-size: 14.5px; max-height: 0; opacity: 0; overflow: hidden; transition: max-height .35s ease, opacity .35s ease, margin .35s; }
        .how-steps button.on .how-txt small { max-height: 80px; opacity: 1; margin-top: 4px; }
        .how-bar { position: absolute; left: 0; right: 0; bottom: 0; height: 3px; }
        .how-bar i { display: block; height: 100%; background: var(--accent); transform-origin: left; animation: how-progress 5s linear forwards; }
        @keyframes how-progress { from { transform: scaleX(0); } to { transform: scaleX(1); } }
        .phone { position: relative; width: 280px; height: 540px; margin: 0 auto; border-radius: 40px; background: var(--brand-dark); padding: 14px; box-shadow: 0 30px 60px rgba(12,65,115,.28), inset 0 0 0 2px rgba(255,255,255,.08); }
        .phone-notch { position: absolute; top: 14px; left: 50%; transform: translateX(-50%); width: 96px; height: 22px; background: var(--brand-dark); border-radius: 0 0 14px 14px; z-index: 2; }
        .screen { position: relative; height: 100%; border-radius: 28px; background: var(--bg); overflow: hidden; }
        .scr { position: absolute; inset: 0; padding: 44px 16px 16px; display: grid; gap: 10px; align-content: start; font-size: 13px; }
        .scr-in { transition: opacity .45s ease, transform .45s ease; }
        .scr-from { opacity: 0; transform: translateY(16px); }
        .m-h { margin: 6px 0 0; font-weight: 700; color: var(--brand); font-size: 14px; }
        .m-search { display: flex; gap: 8px; align-items: center; background: #fff; border: 1.5px solid var(--accent); border-radius: 12px; padding: 10px 12px; color: var(--muted); }
        .m-card { background: #fff; border-radius: 14px; overflow: hidden; border: 1px solid var(--line); display: grid; gap: 2px; padding-bottom: 10px; }
        .m-card.dim { opacity: .6; }
        .m-card b, .m-card small, .m-row { padding: 0 12px; }
        .m-card small { color: var(--muted); }
        .m-cover { height: 70px; margin-bottom: 8px; }
        .c1 { background: linear-gradient(135deg, #1d2b64, #FB7212); }
        .c2 { background: linear-gradient(135deg, #005f73, #f8b500); }
        .m-row { display: flex; justify-content: space-between; align-items: center; margin-top: 6px; }
        .m-btn { background: var(--accent); color: #fff; border-radius: 8px; padding: 4px 10px; font-weight: 600; }
        .m-stop { display: grid; grid-template-columns: 40px 14px 1fr; gap: 8px; align-items: center; background: #fff; border: 1.5px solid var(--line); border-radius: 10px; padding: 9px 10px; }
        .m-stop span { color: var(--brand); font-weight: 600; }
        .m-stop i { width: 12px; height: 12px; border-radius: 50%; border: 3px solid #b7c3d3; }
        .m-stop.on { border-color: var(--accent); background: var(--accent-soft); }
        .m-stop.on i { border-color: var(--accent); background: var(--accent); }
        .m-input { display: flex; justify-content: space-between; background: #fff; border: 1.5px solid var(--line); border-radius: 10px; padding: 9px 10px; }
        .m-input em { font-style: normal; color: var(--muted); font-size: 11.5px; }
        .m-add { border: 1.5px dashed #b9c6d6; color: var(--brand); border-radius: 10px; padding: 8px; text-align: center; font-weight: 600; }
        .m-wave { background: #1dc8ff; color: #04263a; border-radius: 12px; padding: 12px; text-align: center; font-weight: 600; }
        .m-receipt { display: flex; gap: 8px; align-items: center; background: var(--ok-soft); color: var(--ok); border: 1.5px solid #9fd8be; border-radius: 10px; padding: 10px; }
        .m-receipt em { margin-left: auto; font-style: normal; }
        .m-read { display: flex; justify-content: space-between; background: #fff; border: 1.5px solid #b7e2cf; border-radius: 10px; padding: 9px 10px; }
        .m-read span { color: var(--muted); }
        .m-ok { color: var(--ok); font-weight: 600; text-align: center; }
        .m-ticket { background: var(--brand); color: #fff; border-radius: 18px; padding: 16px; display: grid; gap: 8px; justify-items: center; text-align: center; margin-top: 10px; }
        .m-ticket small { color: #ffb07a; font-weight: 600; letter-spacing: .06em; font-size: 10.5px; }
        .m-ticket b { font-size: 16px; }
        .m-ticket > span { color: #c9d8ea; }
        .m-qr { display: grid; grid-template-columns: repeat(7, 18px); gap: 2px; background: #fff; padding: 10px; border-radius: 12px; }
        .m-qr i { width: 18px; height: 18px; background: var(--brand-dark); border-radius: 2px; }
        .m-legs { display: flex; gap: 6px; }
        .m-legs em { font-style: normal; font-size: 11.5px; padding: 3px 9px; border-radius: 99px; background: rgba(255,255,255,.14); }
        .m-legs em.done { background: var(--ok); }
        @media (max-width: 899px) { .phone { width: 250px; height: 480px; } }

        /* Animations d'entrée et au défilement */
        .hero-text > * { animation: monte .7s cubic-bezier(.2,.7,.2,1) both; }
        .hero-text > *:nth-child(2) { animation-delay: .08s; }
        .hero-text > *:nth-child(3) { animation-delay: .16s; }
        .hero-text > *:nth-child(4) { animation-delay: .24s; }
        .hero-text > *:nth-child(5) { animation-delay: .32s; }
        .hero-illu { animation: roule 1.1s cubic-bezier(.2,.7,.2,1) .2s both; }
        @keyframes roule { from { transform: translateX(120px); opacity: 0; } to { transform: none; opacity: .95; } }
        .enter { animation: monte .55s cubic-bezier(.2,.7,.2,1) both; animation-delay: calc(var(--i, 0) * 70ms); }

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
