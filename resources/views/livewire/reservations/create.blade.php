<div class="evt-page">
    @php
        $evt = $this->evenement;
        $depart = $this->depart;
        $tarif = $this->tarif;
        $prix = $this->prixUnitaire;
        $max = $this->maxPossible;
        $complet = $this->canal === 'transport' ? ($depart && $this->restant === 0) : ($this->typeBillet && $this->restant === 0);
        $n = 1;
    @endphp

    <a href="{{ route('home') }}" class="back noprint">← Tous les événements</a>

    {{-- En-tête événement avec affiche --}}
    <header class="evt-hero">
        <x-evenement-cover :evenement="$evt" :badge="false" :eager="true" class="evt-cover" />
        <div class="evt-info">
            <div class="kicker">{{ $evt->debut_at->locale('fr')->translatedFormat('l j F Y · H\hi') }}</div>
            <h1>{{ $evt->titre }}</h1>
            @if ($evt->accroche)
                <p class="lead">{{ $evt->accroche }}</p>
            @endif
            <div class="facts">
                <span>📍 {{ $evt->lieu }}, {{ $evt->ville }}</span>
                @if ($depart)
                    <span>🚌 Départ {{ $depart->depart_at->format('H\hi') }}@if ($depart->retour_at) · retour {{ $depart->retour_at->format('H\hi') }}@endif</span>
                @endif
            </div>
            @if ($evt->description)
                <details class="desc">
                    <summary>À propos de l’événement</summary>
                    <p>{!! nl2br(e($evt->description)) !!}</p>
                </details>
            @endif
        </div>
    </header>

    @if (! $this->open)
        <div class="alert alert-warn">Les réservations sont fermées pour le moment.</div>
    @endif

    @if ($errors->has('reservation') || $errors->has('quantite') || $errors->has('depart_id') || $errors->has('zone_id') || $errors->has('type_billet_id'))
        <div class="alert alert-err" role="alert">
            <div>
                @foreach (['reservation', 'quantite', 'depart_id', 'zone_id', 'type_billet_id'] as $k)
                    @error($k) <div>{{ $message }}</div> @enderror
                @endforeach
            </div>
        </div>
    @endif

    @if ($this->departs->isEmpty() && $this->typesBillets->isEmpty())
        <div class="panel empty">
            <h3>Pas encore de départ ouvert</h3>
            <p class="hint">Les places pour cet événement ne sont pas encore en vente. Revenez bientôt.</p>
        </div>
    @else
        <form class="booking" wire:submit="reserve">
            <div class="panel steps-panel">
                @if ($this->departs->isNotEmpty() && $this->typesBillets->isNotEmpty())
                    <div class="canaux" role="tablist" aria-label="Type de réservation">
                        <button type="button" role="tab" aria-selected="{{ $canal === 'transport' ? 'true' : 'false' }}" class="canal {{ $canal === 'transport' ? 'on' : '' }}" wire:click="$set('canal', 'transport')">🚌 Place en car</button>
                        <button type="button" role="tab" aria-selected="{{ $canal === 'billet' ? 'true' : 'false' }}" class="canal {{ $canal === 'billet' ? 'on' : '' }}" wire:click="$set('canal', 'billet')">🎟️ Billet d’entrée</button>
                    </div>
                @endif

                @if ($canal === 'transport')
                    @if ($this->departs->count() > 1)
                        <fieldset>
                            <legend><span class="num">{{ $n++ }}</span> Choisis ton départ</legend>
                            <div class="chips">
                                @foreach ($this->departs as $d)
                                    @php $reste = $d->placesRestantes(); @endphp
                                    <label class="chip" wire:key="dep-{{ $d->id }}">
                                        <input type="radio" name="depart" wire:model.live="depart_id" value="{{ $d->id }}" @disabled($reste === 0)>
                                        <span>
                                            <b>{{ $d->depart_at->locale('fr')->translatedFormat('D j M · H\hi') }}</b>
                                            <small @class(['low' => $reste <= 3])>{{ $reste === 0 ? 'Complet' : $reste.' place'.($reste > 1 ? 's' : '').' restante'.($reste > 1 ? 's' : '') }}</small>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endif

                    <fieldset>
                        <legend><span class="num">{{ $n++ }}</span> Où montes-tu ?</legend>
                        <p class="hint" style="margin:-4px 0 12px">Le car passe par ces arrêts, dans cet ordre. Choisis le plus proche de chez toi.</p>
                        <ol class="route" aria-label="Itinéraire">
                            @foreach ($this->tarifs as $t)
                                <li class="stop {{ $t->principal ? 'principal' : '' }}" wire:key="z-{{ $t->id }}">
                                    <input type="radio" name="zone" wire:model.live="zone_id" value="{{ $t->zone_id }}" aria-label="{{ $t->zone->nom }}, {{ number_format($t->prix, 0, ',', ' ') }} francs">
                                    <div class="body">
                                        <span class="time">{{ $t->heure() ?? '—' }}</span>
                                        <span class="dot" aria-hidden="true"></span>
                                        <span class="name">
                                            <b>{{ $t->zone->nom }} @if ($t->principal)<span class="tag-main">Point principal</span>@endif</b>
                                            @if ($t->point_rdv)<small>{{ $t->point_rdv }}</small>@endif
                                        </span>
                                        <span class="price">{{ number_format($t->prix, 0, ',', ' ') }} F</span>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                        <div class="route-end"><span></span><span class="flag" aria-hidden="true">🏁</span><span>{{ $evt->lieu }}</span></div>
                    </fieldset>
                @else
                    <fieldset>
                        <legend><span class="num">{{ $n++ }}</span> Catégorie de billet</legend>
                        <div class="opts">
                            @foreach ($this->typesBillets as $cat)
                                @php $reste = $cat->placesRestantes(); @endphp
                                <label class="opt" wire:key="cat-{{ $cat->id }}">
                                    <input type="radio" name="categorie" wire:model.live="type_billet_id" value="{{ $cat->id }}" @disabled($reste === 0)>
                                    <span>
                                        <span>
                                            <b>{{ $cat->nom }}</b>
                                            <small>{{ $cat->description ?: ($reste === 0 ? 'Épuisé' : $reste.' restant(s)') }}</small>
                                        </span>
                                        <span class="price">{{ number_format($cat->prix, 0, ',', ' ') }} F</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif

                <fieldset>
                    <legend><span class="num">{{ $n++ }}</span> {{ $canal === 'transport' ? 'Combien de places ?' : 'Combien de billets ?' }}</legend>
                    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
                        <div class="stepper">
                            <button type="button" wire:click="decrement" aria-label="Retirer une place" @disabled($quantite <= 1)>−</button>
                            <output aria-live="polite">{{ $quantite }}</output>
                            <button type="button" wire:click="increment" aria-label="Ajouter une place" @disabled($quantite >= $max)>+</button>
                        </div>
                        <span class="hint" style="margin:0">
                            @if ($complet)
                                <strong style="color:var(--err)">Complet</strong>
                            @else
                                Max {{ $this->maxParCommande }} par commande · {{ $this->restant }} disponible{{ $this->restant > 1 ? 's' : '' }}
                            @endif
                        </span>
                    </div>
                </fieldset>

                @guest
                    <div class="ident">
                    <fieldset>
                        <legend><span class="num">{{ $n++ }}</span> Tes coordonnées</legend>
                        <div class="seg" role="tablist">
                            <button type="button" role="tab" class="{{ $mode === 'inscription' ? 'on' : '' }}" wire:click="$set('mode', 'inscription')">Je suis nouveau</button>
                            <button type="button" role="tab" class="{{ $mode === 'connexion' ? 'on' : '' }}" wire:click="$set('mode', 'connexion')">J’ai déjà un compte</button>
                        </div>

                        @if ($mode === 'inscription')
                            <p class="hint" style="margin:0 0 12px">Ton compte est créé en même temps : tu retrouveras tes billets dans « Mes billets ».</p>
                            <div class="grid2">
                                <label class="field">Nom complet
                                    <input type="text" wire:model.blur="nom" autocomplete="name" placeholder="Awa Koné">
                                    @error('nom') <span class="err">{{ $message }}</span> @enderror
                                </label>
                                <label class="field">Téléphone (WhatsApp)
                                    <input type="tel" wire:model.blur="tel" inputmode="tel" autocomplete="tel" placeholder="07 00 00 00 00">
                                    @error('tel') <span class="err">{{ $message }}</span> @enderror
                                </label>
                                <label class="field">E-mail
                                    <input type="email" wire:model.blur="email" autocomplete="email" placeholder="awa@exemple.com">
                                    @error('email') <span class="err">{{ $message }}</span> @enderror
                                </label>
                                <label class="field">Mot de passe
                                    <input type="password" wire:model="password" autocomplete="new-password" placeholder="8 caractères minimum">
                                    @error('password') <span class="err">{{ $message }}</span> @enderror
                                </label>
                            </div>
                        @else
                            <div class="grid2">
                                <label class="field">E-mail
                                    <input type="email" wire:model.blur="email" autocomplete="username">
                                    @error('email') <span class="err">{{ $message }}</span> @enderror
                                </label>
                                <label class="field">Mot de passe
                                    <input type="password" wire:model="password" autocomplete="current-password">
                                    @error('password') <span class="err">{{ $message }}</span> @enderror
                                </label>
                            </div>
                            <a class="hint" href="{{ route('password.request') }}">Mot de passe oublié ?</a>
                        @endif
                    </fieldset>
                    </div>
                @endguest
            </div>

            {{-- Récapitulatif --}}
            <aside class="side">
                <div class="ticket recap" aria-label="Récapitulatif">
                    <div class="top">
                        <div class="tag">{{ $canal === 'transport' ? 'Transport aller-retour' : 'Billet d’entrée' }}</div>
                        <div class="zone">{{ $evt->titre }}</div>
                        <div class="who">{{ $evt->debut_at->locale('fr')->translatedFormat('D j M') }} · {{ $evt->lieu }}</div>
                    </div>
                    <div class="perf"></div>
                    <div class="bot">
                        @if ($canal === 'transport')
                            <div class="line"><span>Montée</span><span>{{ $tarif?->zone?->nom ?? '—' }}{{ $tarif?->heure() ? ' · '.$tarif->heure() : '' }}</span></div>
                            @if ($depart)
                                <div class="line"><span>Départ</span><span>{{ $depart->depart_at->locale('fr')->translatedFormat('D j M · H\hi') }}</span></div>
                            @endif
                        @else
                            <div class="line"><span>Catégorie</span><span>{{ $this->typeBillet?->nom ?? '—' }}</span></div>
                        @endif
                        <div class="line"><span>{{ number_format($prix, 0, ',', ' ') }} F × {{ $quantite }}</span><span>{{ number_format($prix * $quantite, 0, ',', ' ') }} F</span></div>
                        <div class="total"><span>Total</span><span>{{ number_format($prix * $quantite, 0, ',', ' ') }} FCFA</span></div>
                    </div>
                </div>
                <ul class="reassure">
                    <li>✅ Paiement Mobile Money (Wave)</li>
                    <li>📲 Billet QR code par e-mail et dans « Mes billets »</li>
                    <li>⏱️ Places bloquées {{ (int) \App\Models\Setting::get('delai_paiement_min', 30) }} min le temps de payer</li>
                </ul>

                <div class="bar">
                    <button class="cta" type="submit" @disabled(! $this->open || $prix < 1 || $complet) wire:loading.attr="disabled" wire:target="reserve">
                        <span wire:loading.remove wire:target="reserve">{{ $complet ? 'Complet' : (auth()->check() ? 'Réserver et payer' : 'Continuer vers le paiement') }}</span>
                        <span wire:loading wire:target="reserve">Un instant…</span>
                        <span class="amt">{{ number_format($prix * $quantite, 0, ',', ' ') }} F</span>
                    </button>
                </div>
            </aside>
        </form>
    @endif

    <style>
        .back { display: inline-block; color: var(--muted); text-decoration: none; font-weight: 600; font-size: 14px; margin-bottom: 14px; }
        .back:hover { color: var(--fg); }
        .evt-hero { display: grid; gap: 20px; margin-bottom: 24px; }
        .evt-cover { border-radius: var(--radius); box-shadow: var(--shadow); }
        @media (min-width: 860px) { .evt-hero { grid-template-columns: 1.1fr 1fr; align-items: center; } }
        .kicker { font: 600 13px var(--body); text-transform: uppercase; letter-spacing: .06em; color: var(--accent-dark); margin-bottom: 8px; }
        .lead { color: var(--muted); font-size: 1.05rem; margin: 0 0 12px; }
        .facts { display: flex; flex-wrap: wrap; gap: 8px; }
        .facts span { background: var(--surface); border: 1px solid var(--line); padding: 6px 12px; border-radius: 99px; font-size: 14px; font-weight: 500; }
        .desc { margin-top: 14px; }
        .desc summary { cursor: pointer; font-weight: 600; color: var(--accent-dark); }
        .desc p { color: var(--muted); }
        .booking { display: grid; gap: 20px; align-items: start; }
        @media (min-width: 900px) { .booking { grid-template-columns: 1.4fr 1fr; } .side { position: sticky; top: 80px; } .side .bar { position: static; background: none; padding: 16px 0 0; } }
        .canaux { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 22px; }
        .canal { min-height: 50px; border: 1.5px solid var(--line); border-radius: 14px; background: var(--soft); font: 700 14px var(--display); cursor: pointer; color: var(--fg); }
        .canal.on { border-color: var(--accent); color: var(--accent-dark); background: var(--accent-soft); box-shadow: inset 0 0 0 1px var(--accent); }
        .seg { display: inline-flex; background: var(--soft); border: 1.5px solid var(--line); border-radius: 12px; padding: 3px; margin-bottom: 12px; }
        .seg button { border: 0; background: none; padding: 8px 14px; border-radius: 9px; font: 600 14px var(--body); cursor: pointer; color: var(--muted); min-height: 40px; }
        .seg button.on { background: #fff; color: var(--fg); box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        .ident { padding-top: 20px; border-top: 1px dashed var(--line); }
        .ident fieldset { margin-bottom: 0; }
        @media (max-width: 899px) {
            .side .bar { position: fixed; left: 0; right: 0; bottom: 0; padding: 10px 16px calc(10px + env(safe-area-inset-bottom)); background: linear-gradient(transparent, var(--bg) 28%); z-index: 20; }
        }
        .reassure { list-style: none; padding: 0; margin: 14px 0 0; display: grid; gap: 6px; color: var(--muted); font-size: 14px; }
        .empty { text-align: center; padding: 40px 20px; }
    </style>
</div>
