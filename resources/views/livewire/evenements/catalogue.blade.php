<div>
    @php $enCours = $this->enCours; @endphp

    @if ($enCours)
        <div class="alert {{ $enCours->statut === \App\Enums\ReservationStatus::APayer ? 'alert-warn' : '' }}" role="status">
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

    <section class="intro">
        <div>
            <h1>Ton car pour l’événement,<br><span style="color:var(--accent)">aller-retour.</span></h1>
            <p>Choisis ton événement, ton arrêt près de chez toi, paie par Mobile Money. Ton billet QR code arrive tout de suite.</p>
        </div>
        <ol class="how" aria-label="Comment ça marche">
            <li><span>1</span><div><b>Choisis</b><small>Événement, départ et arrêt</small></div></li>
            <li><span>2</span><div><b>Paie</b><small>Wave, Orange, MTN, Moov</small></div></li>
            <li><span>3</span><div><b>Monte à bord</b><small>Montre ton QR code</small></div></li>
        </ol>
    </section>

    <div class="section-head">
        <h2>Événements à venir</h2>
        <span class="hint">{{ $this->evenements->count() }} {{ \Illuminate\Support\Str::plural('événement', $this->evenements->count()) }}</span>
    </div>

    @if ($this->evenements->isEmpty())
        <div class="panel empty">
            <h3>Aucun événement pour le moment</h3>
            <p class="hint">Les prochains départs seront publiés ici. Revenez bientôt !</p>
        </div>
    @else
        <div class="cards">
            @foreach ($this->evenements as $evt)
                @php $prix = $evt->prixMin(); $complet = $evt->departs->isNotEmpty() && $evt->departs->every(fn ($d) => $d->placesRestantes() === 0) && $evt->typesBillets->filter->enVente()->every(fn ($t) => $t->placesRestantes() === 0); @endphp
                <a class="card" href="{{ route('evenements.show', $evt) }}" wire:key="evt-{{ $evt->id }}">
                    <x-evenement-cover :evenement="$evt" :eager="$loop->first" />
                    <div class="content">
                        <h3>{{ $evt->titre }}</h3>
                        @if ($evt->accroche)
                            <p class="hint" style="margin:0">{{ $evt->accroche }}</p>
                        @endif
                        <div class="meta">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>
                            {{ $evt->lieu }} · {{ $evt->ville }}
                        </div>
                        <div class="meta">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                            {{ $evt->dateCourte() }}
                        </div>
                        <div class="foot">
                            @if ($complet)
                                <span class="badge annulee">Complet</span>
                            @elseif ($prix)
                                <span class="from">À partir de <b>{{ number_format($prix, 0, ',', ' ') }} F</b></span>
                            @else
                                <span class="from">Bientôt en vente</span>
                            @endif
                            <span class="go">Réserver →</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

    <style>
        .intro { display: grid; gap: 20px; margin: 8px 0 32px; align-items: end; }
        @media (min-width: 860px) { .intro { grid-template-columns: 1.3fr 1fr; } }
        .intro p { color: var(--muted); font-size: 1.05rem; max-width: 52ch; margin: 0; }
        .how { list-style: none; margin: 0; padding: 0; display: grid; gap: 8px; }
        .how li { display: flex; gap: 12px; align-items: center; background: var(--surface); border: 1px solid var(--line); border-radius: 14px; padding: 10px 14px; }
        .how span { width: 30px; height: 30px; border-radius: 50%; background: var(--ink); color: #fff; display: grid; place-items: center; font: 700 14px var(--display); flex: none; }
        .how b { display: block; font-family: var(--display); }
        .how small { color: var(--muted); }
        .section-head { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; margin-bottom: 14px; }
        .section-head h2 { margin: 0; }
        .empty { text-align: center; padding: 40px 20px; }
    </style>
</div>
