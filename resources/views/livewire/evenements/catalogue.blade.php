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
                <p class="hero-long">Réserve ton aller-retour en car vers ton concert, ton festival ou ton match. Ramassage près de chez toi, un billet QR par passager.</p>

                <form class="search" role="search" wire:submit="$refresh" onsubmit="document.getElementById('evenements').scrollIntoView()">
                    <label for="q" class="sr">Rechercher un événement</label>
                    <x-ico name="search" size="22" class="search-ico" />
                    <input id="q" type="search" wire:model.live.debounce.400ms="q" placeholder="Artiste, événement, lieu…" autocomplete="off" enterkeyhint="search">
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
        @php
            $cats = $this->categories;
            $filtre = $q !== '' || $quand !== 'tout' || $cat !== '';
            $icoCat = ['concert' => 'music', 'festival' => 'sparkles', 'match' => 'ball', 'sortie' => 'sun'];
        @endphp
        <div class="sec-head">
            <div>
                <h2 class="sec-title">Événements disponibles</h2>
                <p class="sec-lead" style="margin:0">Choisis ton événement, on s’occupe du trajet.</p>
            </div>
        </div>

        <div class="toolbar" role="group" aria-label="Filtrer les événements">
            @if (count($cats))
                <div class="chips" role="group" aria-label="Catégorie">
                    <button type="button" class="chip {{ $cat === '' ? 'on' : '' }}" wire:click="$set('cat', '')" aria-pressed="{{ $cat === '' ? 'true' : 'false' }}">Tout</button>
                    @foreach ($cats as $cle => $c)
                        <button type="button" class="chip {{ $cat === $cle ? 'on' : '' }}" wire:click="$set('cat', '{{ $cle }}')" aria-pressed="{{ $cat === $cle ? 'true' : 'false' }}">
                            <x-ico :name="$icoCat[$cle] ?? 'calendar'" size="17" /> {{ $c['lib'] }} <small>{{ $c['n'] }}</small>
                        </button>
                    @endforeach
                </div>
            @endif
            <label class="quand">
                <span class="sr">Période</span>
                <x-ico name="calendar" size="17" />
                <select wire:model.live="quand">
                    <option value="tout">Toutes dates</option>
                    <option value="semaine">Cette semaine</option>
                    <option value="mois">Ce mois-ci</option>
                </select>
            </label>
        </div>

        @if ($filtre)
            <p class="result-line" aria-live="polite">
                {{ $nb }} résultat{{ $nb > 1 ? 's' : '' }}@if ($q !== '') pour « {{ $q }} »@endif
                · <button type="button" class="linklike" wire:click="effacer">Effacer les filtres</button>
            </p>
        @endif

        <div wire:loading.class="loading" wire:target="q,quand,cat,effacer">
            @if ($this->evenements->isEmpty())
                <div class="panel empty">
                    <x-ico name="calendar" size="34" />
                    <h3>{{ $filtre ? 'Aucun événement ne correspond' : 'Aucun événement pour le moment' }}</h3>
                    <p class="hint">{{ $filtre ? 'Essaie un autre mot-clé, une autre catégorie ou une autre période.' : 'Les prochains départs seront publiés ici très bientôt.' }}</p>
                    @if ($filtre)
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

    {{-- 4. Fonctionnement : le trajet en 4 arrêts (CSS pur, se joue à l'apparition) --}}
    <div wire:ignore>
    <section id="comment-ca-marche" class="bleed sec-white">
        <div class="in">
            <h2 class="sec-title" data-anim>Comment ça marche ?</h2>
            <p class="sec-lead" data-anim>De ton canapé jusqu’à l’événement : 4 arrêts, 2 minutes.</p>

            <div class="trajet-how" data-anim>
                <div class="rt-road" aria-hidden="true"><i class="rt-fill"></i><span class="rt-bus"><x-ico name="bus" size="22" /></span></div>
                <ol class="rt-stops">
                    @foreach ([
                        ['search', 'Choisis ton événement', 'Concert, festival, match : cherche par nom, lieu ou date.', '30 s'],
                        ['pin', 'Ton arrêt, tes amis', 'Prends l’arrêt le plus proche et ajoute tes passagers par leur nom.', '1 min'],
                        ['wallet', 'Paie en Mobile Money', 'Un seul paiement Wave pour tout le groupe, reçu lu automatiquement.', '30 s'],
                        ['qr', 'Monte à bord', 'Un QR code par passager, scanné à l’aller puis au retour.', 'Jour J'],
                    ] as $i => [$ico, $titre, $texte, $duree])
                        <li class="rt-stop" style="--i: {{ $i }}">
                            <span class="rt-dot"><x-ico :name="$ico" size="22" /></span>
                            <div class="rt-card">
                                <span class="rt-n">Arrêt {{ $i + 1 }} <em>{{ $duree }}</em></span>
                                <b>{{ $titre }}</b>
                                <p>{{ $texte }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
            <p class="rt-cta" data-anim><a class="btn btn-lg" href="#evenements">Voir les prochains événements <x-ico name="arrow-right" size="18" /></a></p>
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
        /* Barre de filtres (catégories + période), collante au défilement */
        .toolbar { position: sticky; top: 74px; z-index: 20; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; justify-content: space-between; padding: 10px 0; margin: -6px 0 18px; background: var(--bg); }
        .chips { display: flex; gap: 8px; overflow-x: auto; scrollbar-width: none; padding: 2px; margin: -2px; min-width: 0; flex: 1; }
        .chips::-webkit-scrollbar { display: none; }
        .chip { display: inline-flex; align-items: center; gap: 7px; flex: none; min-height: 42px; padding: 8px 15px; border-radius: 99px; border: 1.5px solid var(--line); background: #fff; color: var(--fg); font: 600 14.5px var(--body); cursor: pointer; transition: background .15s, border-color .15s, color .15s; }
        .chip small { font-weight: 700; font-size: 12px; min-width: 20px; padding: 1px 6px; border-radius: 99px; background: var(--brand-soft); color: var(--brand); }
        .chip:hover { border-color: var(--brand); }
        .chip.on { background: var(--brand); border-color: var(--brand); color: #fff; }
        .chip.on small { background: rgba(255,255,255,.2); color: #fff; }
        .quand { position: relative; display: inline-flex; align-items: center; gap: 6px; flex: none; min-height: 42px; padding: 0 12px; border: 1.5px solid var(--line); border-radius: 99px; background: #fff; color: var(--brand); }
        .quand select { border: 0; background: transparent; font: 600 14.5px var(--body); color: var(--fg); padding: 8px 4px; cursor: pointer; outline: none; }
        .quand:focus-within { border-color: var(--brand); }
        @media (max-width: 760px) {
            .toolbar { top: 62px; margin-left: -16px; margin-right: -16px; padding: 10px 16px; flex-wrap: nowrap; box-shadow: 0 6px 12px -10px rgba(12,65,115,.35); }
            .chip { min-height: 40px; padding: 7px 13px; font-size: 14px; }
            .quand { padding: 0 8px; } .quand .ico { display: none; } .quand select { field-sizing: content; max-width: 104px; font-size: 14px; }
            .hero-long { display: none; }
            .hero-home .hero-text h1 { margin-bottom: 22px; }
            .hero-home .in { padding-top: 32px !important; padding-bottom: 40px !important; }
            .hero-illu { opacity: .14 !important; bottom: auto; top: 10px; }
            .hero-points { gap: 6px 14px !important; }
        }
        /* Comment ça marche : route animée */
        .trajet-how { position: relative; margin-top: 8px; padding-top: 44px; --dur: 2.6s; }
        .trajet-how .rt-road { top: 71px; }
        .rt-road { position: absolute; left: 12.5%; right: 12.5%; top: 27px; height: 6px; border-radius: 6px; background: repeating-linear-gradient(90deg, #d5deea 0 14px, transparent 14px 24px); }
        .rt-fill { position: absolute; inset: 0; border-radius: 6px; background: linear-gradient(90deg, var(--brand), var(--accent)); transform-origin: left; transition: transform var(--dur) cubic-bezier(.45,.05,.3,1) .3s; }
        .rt-bus { position: absolute; top: 50%; left: 100%; width: 40px; height: 40px; margin: -62px 0 0 -20px; border-radius: 14px; background: var(--accent); color: #fff; display: grid; place-items: center; box-shadow: 0 8px 18px rgba(251,114,18,.4); transition: left var(--dur) cubic-bezier(.45,.05,.3,1) .3s; z-index: 2; }
        .js .trajet-how:not(.vu) .rt-fill { transform: scaleX(0); }
        .js .trajet-how:not(.vu) .rt-bus { left: 0; }
        .trajet-how.vu .rt-bus { animation: rt-bob 1.4s ease-in-out calc(var(--dur) + .3s) infinite; }
        @keyframes rt-bob { 50% { transform: translateY(-4px); } }
        .rt-bus::after { content: ''; position: absolute; left: 50%; bottom: -8px; margin-left: -6px; border: 6px solid transparent; border-top-color: var(--accent); border-bottom: 0; }
        .rt-stops { list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; position: relative; }
        .rt-stop { display: grid; grid-template-rows: auto 1fr; justify-items: center; text-align: center; gap: 14px; }
        .rt-card { height: 100%; }
        .rt-dot { width: 60px; height: 60px; border-radius: 50%; display: grid; place-items: center; background: var(--brand); color: #fff; border: 3px solid var(--brand); position: relative; z-index: 1; transition: background .35s, color .35s, border-color .35s, transform .35s; transition-delay: calc(.3s + var(--i) * var(--dur) / 3); }
        .js .trajet-how:not(.vu) .rt-dot { background: #fff; color: var(--muted); border-color: #d5deea; transform: scale(.9); }
        .rt-stop:last-child .rt-dot { background: var(--accent); border-color: var(--accent); }
        .rt-card { background: #fff; border: 1.5px solid var(--line); border-radius: var(--r); padding: 16px; width: 100%; transition: opacity .5s, transform .5s, border-color .2s, box-shadow .2s; transition-delay: calc(.4s + var(--i) * var(--dur) / 3), calc(.4s + var(--i) * var(--dur) / 3), 0s, 0s; }
        .js .trajet-how:not(.vu) .rt-card { opacity: .3; transform: translateY(10px); }
        .rt-stop:hover .rt-card { border-color: var(--brand); box-shadow: var(--shadow); }
        .rt-n { display: flex; justify-content: center; gap: 8px; align-items: center; font: 700 12.5px var(--body); letter-spacing: .05em; text-transform: uppercase; color: var(--accent); }
        .rt-n em { font-style: normal; text-transform: none; letter-spacing: 0; font-weight: 600; color: var(--brand); background: var(--brand-soft); padding: 2px 8px; border-radius: 99px; }
        .rt-card b { display: block; margin: 6px 0 4px; font-size: 1.08rem; color: var(--fg); }
        .rt-card p { margin: 0; color: var(--muted); font-size: 14.5px; }
        .rt-cta { text-align: center; margin: 28px 0 0; }
        @media (max-width: 760px) {
            .rt-road { left: 27px; right: auto; top: 30px; bottom: 30px; width: 6px; height: auto; background: repeating-linear-gradient(180deg, #d5deea 0 14px, transparent 14px 24px); }
            .rt-fill { transform-origin: top; background: linear-gradient(180deg, var(--brand), var(--accent)); }
            .js .trajet-how:not(.vu) .rt-fill { transform: scaleY(0); }
            .rt-bus { left: 50%; top: 100%; margin: -20px 0 0 -20px; transition: top var(--dur) cubic-bezier(.45,.05,.3,1) .3s, opacity .4s calc(var(--dur) + .3s); opacity: 0; }
            .rt-bus::after { display: none; }
            .js .trajet-how:not(.vu) .rt-bus { opacity: 1; }
            .trajet-how.vu .rt-bus { animation: none; }
            .js .trajet-how:not(.vu) .rt-bus { left: 50%; top: 0; }
            .trajet-how { padding-top: 0; }
            .trajet-how .rt-road { top: 30px; }
            .rt-stops { grid-template-columns: 1fr; gap: 14px; }
            .rt-stop { grid-template-rows: auto; grid-template-columns: 60px 1fr; justify-items: start; text-align: left; align-items: start; }
            .rt-n { justify-content: flex-start; }
            .rt-card { padding: 14px; }
        }
        @media (prefers-reduced-motion: reduce) { .rt-fill, .rt-bus, .rt-dot, .rt-card { transition: none !important; animation: none !important; } }
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
        .c1 { background: linear-gradient(135deg, #1d2b64, #FB7212); }
        .c2 { background: linear-gradient(135deg, #005f73, #f8b500); }

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
