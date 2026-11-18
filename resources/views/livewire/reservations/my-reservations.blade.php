<div class="mesresa">
    <h1 class="page-title">Mes réservations</h1>

    @if ($this->reservations->isEmpty())
        <div class="resa-card" style="text-align:center;padding:40px 20px">
            <h3>Aucune réservation pour l’instant</h3>
            <p class="hint">Choisis un événement et réserve ton aller-retour en deux minutes.</p>
            <a class="btn" href="{{ route('home') }}#evenements" style="margin-top:14px">Voir les événements</a>
        </div>
    @endif

    <div class="resa-list">
        @foreach ($this->reservations as $r)
            @php
                $evt = $r->depart?->evenement ?? $r->typeBillet?->evenement;
                $passagers = $r->billets->pluck('nom_passager')->filter()->unique();
                if ($passagers->isEmpty()) { $passagers = collect([auth()->user()->name]); }
                [$badge, $classe] = match ($r->statut) {
                    \App\Enums\ReservationStatus::APayer => ['En attente', 'wait'],
                    \App\Enums\ReservationStatus::EnVerification => ['En vérification', 'check'],
                    \App\Enums\ReservationStatus::Payee => ['Confirmée', 'ok'],
                    default => [$r->statut->getLabel(), 'off'],
                };
            @endphp
            <article class="resa-card" wire:key="r-{{ $r->id }}">
                <div class="resa-head">
                    <div>
                        <div class="ref">{{ $r->ref }}</div>
                        <h2>{{ $evt?->titre ?? 'Réservation' }}</h2>
                    </div>
                    <span class="st st-{{ $classe }}">{{ $badge }}</span>
                </div>

                <p class="resa-meta">
                    {{ $r->quantite }} place(s) · <strong>{{ number_format($r->montant, 0, ',', ' ') }} FCFA</strong>
                    @if ($r->zone)
                        <span class="sep">·</span> Montée à {{ $r->zone->nom }}
                    @endif
                    @if ($evt)
                        <span class="sep">·</span> {{ $evt->debut_at->locale('fr')->translatedFormat('D j M') }}
                    @endif
                </p>

                <ul class="pax">
                    @foreach ($passagers as $nom)
                        <li>{{ $nom }}</li>
                    @endforeach
                </ul>

                @if ($r->statut === \App\Enums\ReservationStatus::APayer)
                    <div class="note-box note-wait">
                        <span>Paiement attendu avant {{ $r->expire_at?->format('H\hi') ?? '—' }}, sinon les places sont libérées.</span>
                        <a class="btn btn-sm" href="{{ route('reservations.show', $r) }}">Payer maintenant</a>
                    </div>
                @elseif ($r->statut === \App\Enums\ReservationStatus::EnVerification)
                    <div class="note-box note-check">
                        <span>Reçu bien reçu : notre équipe vérifie ton paiement. Tes billets arrivent par e-mail.</span>
                        <a class="btn btn-sm btn-ghost" href="{{ route('reservations.show', $r) }}">Suivre</a>
                    </div>
                @elseif ($r->statut === \App\Enums\ReservationStatus::Payee)
                    <div class="note-box note-ok">
                        <span>Paiement validé. Présente ton QR code à l’agent au moment de monter.</span>
                        <a class="btn btn-sm" href="{{ route('reservations.show', $r) }}">Voir mes billets</a>
                    </div>
                @endif
            </article>
        @endforeach
    </div>

    <style>
        .mesresa { max-width: 1080px; margin: 0 auto; }
        .page-title { font: 700 clamp(1.8rem, 4vw, 2.2rem) var(--display); color: var(--fg); margin: 12px 0 26px; }
        .resa-list { display: grid; gap: 22px; }
        .resa-card { background: #fff; border: 1.5px solid var(--brand); border-radius: 18px; padding: 24px 24px 22px; }
        .resa-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
        .resa-card .ref { color: var(--muted); font-size: 15px; letter-spacing: .02em; }
        .resa-card h2 { font: 600 1.35rem var(--display); margin: 2px 0 0; color: var(--fg); }
        .resa-meta { margin: 14px 0 8px; font-size: 1.08rem; color: var(--fg); }
        .resa-meta .sep { color: var(--muted); }
        .pax { margin: 0 0 16px; padding-left: 26px; color: var(--fg); font-size: 1.05rem; }
        .st { font: 500 15px var(--body); padding: 6px 16px; border-radius: 99px; white-space: nowrap; }
        .st-wait { background: #fdf3d7; color: #9a5b00; }
        .st-check { background: #e6eef8; color: var(--brand); }
        .st-ok { background: var(--ok-soft); color: var(--ok); }
        .st-off { background: #eef1f5; color: var(--muted); }
        .note-box { border-radius: 12px; padding: 14px 18px; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 10px 16px; font-size: 1.05rem; }
        .note-wait { background: #fdf8e8; color: #9a5b00; }
        .note-check { background: #eef3fa; color: var(--brand); }
        .note-ok { background: var(--ok-soft); color: var(--ok); }
    </style>
</div>
