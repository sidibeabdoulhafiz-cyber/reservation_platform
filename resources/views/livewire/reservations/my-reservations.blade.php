<div class="espace medium">
    @php
        $prochain = $this->prochain;
        $listes = ['a-venir' => $this->aVenir, 'a-finaliser' => $this->aFinaliser, 'historique' => $this->historique];
        $billetsActifs = $this->aVenir->sum(fn ($r) => $r->billets->where('statut', \App\Enums\BilletStatus::Valide)->count());
    @endphp

    <header class="page-head">
        <div>
            <p class="kicker" style="margin:0 0 4px">Bonjour {{ \Illuminate\Support\Str::of(auth()->user()->name)->before(' ') }}</p>
            <h1>Mes réservations</h1>
        </div>
        <a href="{{ route('home') }}#evenements" class="btn btn-sm"><x-ico name="plus" size="18" /> Nouvelle réservation</a>
    </header>

    {{-- Prochain départ --}}
    @if ($prochain)
        @php $evt = $prochain->evenement(); @endphp
        <section class="next">
            <div class="next-head">
                <div>
                    <span class="next-kicker"><x-ico name="bus" size="16" /> Prochain départ · {{ $prochain->depart?->depart_at?->locale('fr')->diffForHumans() ?? $evt?->debut_at?->locale('fr')->diffForHumans() }}</span>
                    <h2>{{ $evt?->titre }}</h2>
                </div>
                <a href="{{ route('reservations.show', $prochain) }}" class="btn"><x-ico name="qr" size="18" /> Mes billets</a>
            </div>
            @if ($prochain->depart)
                <x-trajet :reservation="$prochain" class="next-trajet" />
            @endif
            <p class="next-pax"><x-ico name="users" size="16" /> {{ implode(', ', $prochain->nomsPassagers()) }}</p>
        </section>
    @endif

    {{-- Indicateurs --}}
    <div class="stats">
        <button type="button" class="stat {{ $onglet === 'a-finaliser' ? 'on' : '' }}" wire:click="choisir('a-finaliser')">
            <span class="s-ico warn"><x-ico name="wallet" size="20" /></span>
            <span><b>{{ $this->aFinaliser->count() }}</b><small>à finaliser</small></span>
        </button>
        <button type="button" class="stat {{ $onglet === 'a-venir' ? 'on' : '' }}" wire:click="choisir('a-venir')">
            <span class="s-ico"><x-ico name="ticket" size="20" /></span>
            <span><b>{{ $billetsActifs }}</b><small>billet{{ $billetsActifs > 1 ? 's' : '' }} à venir</small></span>
        </button>
        <button type="button" class="stat {{ $onglet === 'historique' ? 'on' : '' }}" wire:click="choisir('historique')">
            <span class="s-ico muted-ico"><x-ico name="clock" size="20" /></span>
            <span><b>{{ $this->historique->count() }}</b><small>dans l’historique</small></span>
        </button>
    </div>

    {{-- Onglets --}}
    <div class="tabs" role="tablist" aria-label="Réservations">
        @foreach (['a-venir' => 'À venir', 'a-finaliser' => 'À finaliser', 'historique' => 'Historique'] as $cle => $lib)
            <button type="button" role="tab" aria-selected="{{ $onglet === $cle ? 'true' : 'false' }}" class="{{ $onglet === $cle ? 'on' : '' }}" wire:click="choisir('{{ $cle }}')">
                {{ $lib }} <span class="count">{{ $listes[$cle]->count() }}</span>
            </button>
        @endforeach
    </div>

    @php $liste = $listes[$onglet] ?? collect(); @endphp

    @if ($liste->isEmpty())
        <div class="panel empty">
            <x-ico name="{{ $onglet === 'historique' ? 'clock' : 'ticket' }}" size="34" />
            <h3>
                @if ($onglet === 'a-venir') Aucun voyage à venir
                @elseif ($onglet === 'a-finaliser') Rien à finaliser
                @else Pas encore d’historique
                @endif
            </h3>
            <p class="hint">{{ $onglet === 'a-finaliser' ? 'Toutes tes réservations sont réglées.' : 'Choisis un événement et réserve ton aller-retour en deux minutes.' }}</p>
            @if ($onglet !== 'a-finaliser')
                <a class="btn" href="{{ route('home') }}#evenements" style="margin-top:14px">Voir les événements</a>
            @endif
        </div>
    @else
        <div class="resa-list">
            @foreach ($liste as $r)
                @php
                    $evt = $r->evenement();
                    [$badge, $classe] = match ($r->statut) {
                        \App\Enums\ReservationStatus::APayer => ['À payer', 'a_payer'],
                        \App\Enums\ReservationStatus::EnVerification => ['En vérification', 'en_verification'],
                        \App\Enums\ReservationStatus::Payee => ['Confirmée', 'payee'],
                        default => [$r->statut->getLabel(), 'annulee'],
                    };
                @endphp
                <article class="resa-card" wire:key="r-{{ $r->id }}">
                    <a href="{{ route('reservations.show', $r) }}" class="resa-link">
                        @if ($evt)
                            <x-evenement-cover :evenement="$evt" :badge="false" class="resa-thumb" />
                        @endif
                        <div class="resa-main">
                            <div class="resa-top">
                                <span class="mono muted">{{ $r->ref }}</span>
                                <span class="badge {{ $classe }}">{{ $badge }}</span>
                            </div>
                            <h3>{{ $evt?->titre ?? 'Réservation' }}</h3>
                            <ul class="resa-meta">
                                @if ($evt)<li><x-ico name="calendar" size="15" /> {{ ($r->depart?->depart_at ?? $evt->debut_at)->locale('fr')->translatedFormat('D j M · H\hi') }}</li>@endif
                                @if ($r->zone)<li><x-ico name="pin" size="15" /> {{ $r->zone->nom }}</li>@endif
                                <li><x-ico name="users" size="15" /> {{ $r->quantite }} passager{{ $r->quantite > 1 ? 's' : '' }}</li>
                                <li><x-ico name="wallet" size="15" /> {{ number_format($r->montant, 0, ',', ' ') }} FCFA</li>
                            </ul>
                        </div>
                        <x-ico name="chevron-right" class="resa-chev" />
                    </a>

                    @if ($r->statut === \App\Enums\ReservationStatus::APayer)
                        <div class="note-box note-wait">
                            <span><x-ico name="clock" size="16" /> Paiement attendu avant {{ $r->expire_at?->format('H\hi') ?? '—' }}, sinon les places sont libérées.</span>
                            <a class="btn btn-sm" href="{{ route('reservations.show', $r) }}">Payer maintenant</a>
                        </div>
                    @elseif ($r->statut === \App\Enums\ReservationStatus::EnVerification)
                        <div class="note-box note-check">
                            <span><x-ico name="shield" size="16" /> Reçu en cours de vérification. Tes billets arrivent par e-mail.</span>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    @endif

    <style>
        .next { background: var(--brand); color: #fff; border-radius: var(--r-lg); padding: 22px; margin-bottom: 18px; box-shadow: 0 14px 36px rgba(12,65,115,.22); }
        .next-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 14px; flex-wrap: wrap; margin-bottom: 16px; }
        .next-kicker { display: inline-flex; align-items: center; gap: 6px; font: 600 13px var(--body); text-transform: uppercase; letter-spacing: .05em; color: #ffb07a; }
        .next h2 { color: #fff; margin: 6px 0 0; }
        .next-trajet { background: rgba(255,255,255,.07); border-radius: 14px; padding: 14px; }
        .next-trajet .t-body span { color: #c9d8ea; }
        .next-trajet .t-body .ico { color: #ffb07a; }
        .next-trajet .t-tag { background: #fff; color: var(--brand); }
        .next-trajet .t-tag.ret { background: var(--accent); color: #fff; }
        .next-pax { margin: 14px 0 0; color: #c9d8ea; display: flex; gap: 8px; align-items: center; font-size: 14.5px; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 20px; }
        .stat { display: flex; align-items: center; gap: 12px; background: #fff; border: 1.5px solid var(--line); border-radius: var(--r); padding: 14px; cursor: pointer; text-align: left; font: inherit; color: inherit; transition: border-color .15s; }
        .stat:hover, .stat.on { border-color: var(--brand); }
        .stat b { display: block; font: 700 1.5rem/1 var(--display); color: var(--fg); }
        .stat small { color: var(--muted); font-size: 13.5px; }
        .s-ico { width: 42px; height: 42px; border-radius: 12px; display: grid; place-items: center; background: var(--brand-soft); color: var(--brand); flex: none; }
        .s-ico.warn { background: var(--warn-soft); color: var(--warn); }
        .s-ico.muted-ico { background: #eef1f5; color: var(--muted); }
        @media (max-width: 560px) { .stats { gap: 8px; } .stat { flex-direction: column; align-items: flex-start; gap: 8px; padding: 12px; } .s-ico { width: 36px; height: 36px; } }
        .tabs { display: flex; gap: 4px; border-bottom: 1px solid var(--line); margin-bottom: 18px; overflow-x: auto; scrollbar-width: none; }
        .tabs button { border: 0; background: none; font: 500 15.5px var(--body); color: var(--muted); padding: 12px 14px; cursor: pointer; border-bottom: 2.5px solid transparent; margin-bottom: -1px; white-space: nowrap; display: inline-flex; gap: 8px; align-items: center; }
        .tabs button.on { color: var(--brand); border-color: var(--accent); font-weight: 600; }
        .tabs .count { background: #eef1f5; color: var(--muted); font-size: 12.5px; border-radius: 99px; padding: 1px 8px; }
        .tabs button.on .count { background: var(--brand-soft); color: var(--brand); }
        .resa-list { display: grid; gap: 14px; }
        .resa-card { background: #fff; border: 1px solid var(--line); border-radius: var(--r); box-shadow: var(--shadow-sm); overflow: hidden; transition: border-color .15s, box-shadow .15s; }
        .resa-card:hover { border-color: #c3cdda; box-shadow: var(--shadow); }
        .resa-link { display: flex; gap: 14px; align-items: center; padding: 14px; text-decoration: none; }
        .resa-thumb { width: 104px; flex: none; border-radius: 12px; }
        .resa-thumb .fallback { font-size: .7rem; }
        .resa-main { flex: 1; min-width: 0; }
        .resa-top { display: flex; justify-content: space-between; align-items: center; gap: 8px; font-size: 13px; }
        .resa-main h3 { margin: 4px 0 6px; font-size: 1.12rem; }
        .resa-meta { list-style: none; margin: 0; padding: 0; display: flex; flex-wrap: wrap; gap: 4px 14px; color: var(--muted); font-size: 14px; }
        .resa-meta li { display: inline-flex; gap: 5px; align-items: center; }
        .resa-meta .ico { color: var(--brand); }
        .resa-chev { color: #9aa5b4; }
        .note-box { margin: 0 14px 14px; border-radius: 12px; padding: 12px 14px; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 10px 16px; font-size: 14.5px; }
        .note-box span { display: inline-flex; gap: 8px; align-items: center; }
        .note-wait { background: var(--warn-soft); color: var(--warn); }
        .note-check { background: var(--brand-soft); color: var(--brand); }
        @media (max-width: 560px) { .resa-thumb { width: 72px; } .resa-chev { display: none; } .note-box .btn { width: 100%; } }
    </style>
</div>
