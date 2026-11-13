<div class="narrow mesbillets">
    <header class="hero">
        <h1>Mes billets</h1>
        <p>Tes réservations et tes QR codes, au même endroit.</p>
    </header>

    @php [$aFinaliser, $aVenir, $passees] = $this->groupes; @endphp

    @if ($this->reservations->isEmpty())
        <div class="panel" style="text-align:center;padding:36px 20px">
            <h3>Aucune réservation pour l’instant</h3>
            <p class="hint">Choisis un événement et réserve ta place en car en deux minutes.</p>
            <a class="btn" href="{{ route('home') }}" style="margin-top:12px">Voir les événements</a>
        </div>
    @endif

    @foreach ([['À finaliser', $aFinaliser], ['À venir', $aVenir], ['Historique', $passees]] as [$titre, $liste])
        @if ($liste->isNotEmpty())
            <h2 class="grp">{{ $titre }}</h2>
            <div class="list">
                @foreach ($liste as $r)
                    @php $evt = $r->depart?->evenement ?? $r->typeBillet?->evenement; @endphp
                    <a class="row" href="{{ route('reservations.show', $r) }}" wire:key="r-{{ $r->id }}">
                        @if ($evt)
                            <x-evenement-cover :evenement="$evt" :badge="false" class="thumb" />
                        @endif
                        <div class="txt">
                            <b>{{ $evt?->titre ?? 'Réservation' }}</b>
                            <small>
                                {{ $evt?->debut_at?->locale('fr')->translatedFormat('D j M') }}
                                · {{ $r->quantite }} {{ $r->type === \App\Enums\ReservationType::Transport ? 'place' : 'billet' }}{{ $r->quantite > 1 ? 's' : '' }}
                                @if ($r->zone) · {{ $r->zone->nom }} @endif
                            </small>
                            <span class="badge {{ $r->statut->value }}">{{ $r->statut === \App\Enums\ReservationStatus::APayer ? 'À payer · continuer' : $r->statut->getLabel() }}</span>
                        </div>
                        <span class="chev" aria-hidden="true">›</span>
                    </a>
                @endforeach
            </div>
        @endif
    @endforeach

    <style>
        .grp { font-size: 1.1rem; margin: 22px 0 10px; color: var(--muted); text-transform: uppercase; letter-spacing: .05em; font-family: var(--body); font-weight: 700; }
        .list { display: grid; gap: 10px; }
        .row { display: flex; align-items: center; gap: 14px; background: var(--surface); border: 1px solid var(--line); border-radius: 16px; padding: 10px; text-decoration: none; box-shadow: var(--shadow); transition: border-color .15s; }
        .row:hover { border-color: var(--accent); }
        .row .thumb { width: 88px; flex: none; border-radius: 12px; }
        .row .thumb .fallback { font-size: .7rem; }
        .row .txt { display: grid; gap: 3px; flex: 1; min-width: 0; justify-items: start; }
        .row .txt b { font-family: var(--display); font-size: 1.05rem; }
        .row .txt small { color: var(--muted); }
        .chev { font-size: 28px; color: var(--muted); padding: 0 6px; }
    </style>
</div>
