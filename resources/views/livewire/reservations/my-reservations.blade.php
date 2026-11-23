<div class="mesresa narrow">
    @php
        $aFaire = $this->aFinaliser;
        $voyages = $this->aVenir;
        $passes = $this->historique;
        $rien = $aFaire->isEmpty() && $voyages->isEmpty() && $passes->isEmpty();
    @endphp

    <header class="mr-head">
        <p class="kicker">Bonjour {{ \Illuminate\Support\Str::of(auth()->user()->name)->before(' ') }}</p>
        <h1>Mes réservations</h1>
    </header>

    @if ($rien)
        <section class="mr-empty">
            <span class="mr-empty-ico"><x-ico name="ticket" size="34" /></span>
            <h2>Aucune réservation pour l’instant</h2>
            <p>Choisissez un événement : votre aller-retour se réserve en 2 minutes.</p>
            <a class="btn btn-lg" href="{{ route('home') }}#evenements">Voir les événements</a>
        </section>
    @endif

    {{-- 1. Ce qui demande une action --}}
    @if ($aFaire->isNotEmpty())
        <section class="mr-sec">
            <h2 class="mr-title"><span class="dot warn"></span> À faire maintenant</h2>
            @foreach ($aFaire as $r)
                @php $evt = $r->evenement(); $aPayer = $r->statut === \App\Enums\ReservationStatus::APayer; @endphp
                <a href="{{ route('reservations.show', $r) }}" class="todo {{ $aPayer ? 'todo-pay' : 'todo-check' }}" wire:key="t-{{ $r->id }}">
                    <span class="todo-ico"><x-ico name="{{ $aPayer ? 'wallet' : 'clock' }}" size="22" /></span>
                    <span class="todo-txt">
                        <b>{{ $evt?->titre ?? 'Réservation' }}</b>
                        @if ($aPayer)
                            <span>Payez {{ number_format($r->montant, 0, ',', ' ') }} FCFA{{ $r->expire_at ? ' avant '.$r->expire_at->format('H\hi') : '' }}</span>
                        @else
                            <span>Paiement en vérification : vos billets arrivent bientôt</span>
                        @endif
                    </span>
                    <span class="todo-go">{{ $aPayer ? 'Payer' : 'Suivre' }} <x-ico name="arrow-right" size="16" /></span>
                </a>
            @endforeach
        </section>
    @endif

    {{-- 2. Les voyages confirmés à venir --}}
    @if ($voyages->isNotEmpty())
        <section class="mr-sec">
            <h2 class="mr-title"><span class="dot ok"></span> Mes prochains voyages</h2>
            @foreach ($voyages as $r)
                @php
                    $evt = $r->evenement();
                    $quand = $this->dateDepart($r);
                    $arret = $r->depart?->tarifs->firstWhere('zone_id', $r->zone_id);
                    $nbBillets = $r->billets->count() ?: $r->quantite;
                @endphp
                <article class="trip {{ $loop->first ? 'trip-next' : '' }}" wire:key="v-{{ $r->id }}">
                    @if ($loop->first)
                        <p class="trip-when"><x-ico name="bus" size="16" /> Prochain départ · {{ $quand?->locale('fr')->diffForHumans() }}</p>
                    @endif
                    <div class="trip-top">
                        @if ($quand)
                            <span class="trip-date" aria-hidden="true">
                                <b>{{ $quand->format('d') }}</b>
                                <small>{{ $quand->locale('fr')->translatedFormat('M') }}</small>
                            </span>
                        @endif
                        <div class="trip-info">
                            <h3>{{ $evt?->titre }}</h3>
                            @if ($r->depart)
                                <p><x-ico name="clock" size="15" /> {{ $quand->locale('fr')->translatedFormat('l') }} · passage vers <b>{{ $arret?->heure() ?? $quand->format('H\hi') }}</b></p>
                                <p><x-ico name="pin" size="15" /> {{ $r->zone?->nom }}{{ $arret?->point_rdv ? ' · '.$arret->point_rdv : '' }}</p>
                                @if ($r->depart->retour_at)
                                    <p><x-ico name="flag" size="15" /> Retour vers {{ $r->depart->retour_at->format('H\hi') }}</p>
                                @endif
                            @else
                                <p><x-ico name="pin" size="15" /> {{ $evt?->lieu }}</p>
                            @endif
                            <p><x-ico name="users" size="15" /> {{ implode(', ', $r->nomsPassagers()) }}</p>
                        </div>
                    </div>
                    <a href="{{ route('reservations.show', $r) }}" class="btn trip-btn"><x-ico name="qr" size="20" /> Afficher {{ $nbBillets > 1 ? 'mes '.$nbBillets.' billets' : 'mon billet' }}</a>
                </article>
            @endforeach
        </section>
    @endif

    @unless ($rien)
        <a href="{{ route('home') }}#evenements" class="mr-new"><x-ico name="plus" size="18" /> Réserver un autre trajet</a>
    @endunless

    {{-- 3. Historique, replié --}}
    @if ($passes->isNotEmpty())
        <details class="mr-hist">
            <summary>Historique <span>{{ $passes->count() }}</span></summary>
            <ul>
                @foreach ($passes as $r)
                    @php $evt = $r->evenement(); @endphp
                    <li wire:key="h-{{ $r->id }}">
                        <a href="{{ route('reservations.show', $r) }}">
                            <span class="h-txt">
                                <b>{{ $evt?->titre ?? 'Réservation' }}</b>
                                <small>{{ ($this->dateDepart($r))?->locale('fr')->translatedFormat('j M Y') }} · {{ $r->ref }}</small>
                            </span>
                            <span class="badge {{ $r->statut->value }}">{{ $r->statut === \App\Enums\ReservationStatus::Payee ? 'Terminé' : $r->statut->getLabel() }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </details>
    @endif

    <style>
        .mesresa { padding-bottom: 8px; }
        .mr-head { margin: 4px 0 18px; }
        .mr-head .kicker { margin: 0 0 2px; }
        .mr-head h1 { margin: 0; }
        .mr-sec { margin-bottom: 22px; display: grid; gap: 10px; }
        .mr-title { display: flex; align-items: center; gap: 8px; font-size: 1.05rem; margin: 0 0 2px; color: var(--fg); }
        .dot { width: 10px; height: 10px; border-radius: 50%; flex: none; }
        .dot.warn { background: var(--accent); }
        .dot.ok { background: var(--ok); }

        /* À faire */
        .todo { display: flex; align-items: center; gap: 12px; padding: 14px; border-radius: 16px; text-decoration: none; color: var(--fg); border: 1.5px solid transparent; min-height: 72px; }
        .todo-pay { background: var(--warn-soft); border-color: #f8c9a6; }
        .todo-check { background: var(--brand-soft); border-color: #c9d8ea; }
        .todo-ico { width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center; flex: none; background: #fff; }
        .todo-pay .todo-ico { color: var(--accent-dark); }
        .todo-check .todo-ico { color: var(--brand); }
        .todo-txt { flex: 1; min-width: 0; display: grid; gap: 2px; }
        .todo-txt b { font-size: 1rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .todo-txt span { font-size: 14px; color: var(--muted); }
        .todo-go { flex: none; display: inline-flex; align-items: center; gap: 4px; font-weight: 700; font-size: 14.5px; padding: 9px 14px; border-radius: 99px; background: #fff; }
        .todo-pay .todo-go { background: var(--accent); color: #fff; }
        .todo-check .todo-go { color: var(--brand); }

        /* Voyages */
        .trip { background: #fff; border: 1px solid var(--line); border-radius: 18px; padding: 16px; box-shadow: var(--shadow-sm); display: grid; gap: 14px; }
        .trip-next { border: 2px solid var(--brand); box-shadow: 0 10px 28px rgba(12,65,115,.12); }
        .trip-when { margin: 0; display: inline-flex; align-items: center; gap: 6px; font: 700 12.5px var(--body); text-transform: uppercase; letter-spacing: .05em; color: var(--accent-dark); }
        .trip-top { display: flex; gap: 14px; align-items: flex-start; }
        .trip-date { flex: none; width: 58px; border-radius: 14px; background: var(--brand); color: #fff; text-align: center; padding: 8px 0 7px; display: grid; }
        .trip-date b { font: 800 1.55rem/1 var(--display); }
        .trip-date small { font: 700 12px var(--body); text-transform: uppercase; letter-spacing: .06em; color: #ffb07a; margin-top: 3px; }
        .trip-info { min-width: 0; flex: 1; }
        .trip-info h3 { margin: 0 0 6px; font-size: 1.12rem; line-height: 1.25; }
        .trip-info p { margin: 0 0 4px; display: flex; gap: 7px; align-items: flex-start; color: var(--muted); font-size: 14.5px; line-height: 1.35; }
        .trip-info p .ico { color: var(--brand); flex: none; margin-top: 1px; }
        .trip-info p b { color: var(--fg); }
        .trip-btn { width: 100%; justify-content: center; min-height: 52px; font-size: 16px; }

        .mr-new { display: flex; align-items: center; justify-content: center; gap: 8px; min-height: 50px; border: 1.5px dashed #b9c6d6; border-radius: 14px; color: var(--brand); font-weight: 700; text-decoration: none; margin: 0 0 22px; }
        .mr-new:hover { border-color: var(--brand); background: var(--brand-soft); }

        /* Historique */
        .mr-hist { background: #fff; border: 1px solid var(--line); border-radius: 16px; }
        .mr-hist summary { cursor: pointer; padding: 16px; font-weight: 700; display: flex; align-items: center; gap: 8px; min-height: 54px; list-style: none; }
        .mr-hist summary::-webkit-details-marker { display: none; }
        .mr-hist summary::after { content: ''; margin-left: auto; width: 9px; height: 9px; border-right: 2px solid var(--muted); border-bottom: 2px solid var(--muted); transform: rotate(45deg); transition: transform .2s; }
        .mr-hist[open] summary::after { transform: rotate(-135deg); }
        .mr-hist summary span { font-size: 12.5px; background: #eef1f5; color: var(--muted); border-radius: 99px; padding: 1px 8px; }
        .mr-hist ul { list-style: none; margin: 0; padding: 0 8px 8px; }
        .mr-hist li a { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 12px 8px; text-decoration: none; color: var(--fg); border-top: 1px solid var(--line); }
        .h-txt { min-width: 0; display: grid; }
        .h-txt b { font-size: 15px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .h-txt small { color: var(--muted); font-size: 13px; }

        /* Vide */
        .mr-empty { text-align: center; padding: 36px 18px; background: #fff; border: 1px solid var(--line); border-radius: 18px; margin-bottom: 22px; }
        .mr-empty-ico { width: 72px; height: 72px; border-radius: 50%; display: inline-grid; place-items: center; background: var(--brand-soft); color: var(--brand); margin-bottom: 10px; }
        .mr-empty h2 { margin: 0 0 6px; font-size: 1.25rem; }
        .mr-empty p { color: var(--muted); margin: 0 0 18px; }

        @media (max-width: 480px) {
            .todo { padding: 12px; gap: 10px; }
            .todo-ico { width: 40px; height: 40px; }
            .todo-go { padding: 8px 11px; font-size: 13.5px; }
            .trip { padding: 14px; }
            .trip-date { width: 52px; }
        }
    </style>
</div>
