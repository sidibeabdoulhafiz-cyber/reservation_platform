<div class="evt-page">
    @php
        $evt = $this->evenement;
        $depart = $this->depart;
        $tarif = $this->tarif;
        $prix = $this->prixUnitaire;
        $max = $this->maxPossible;
        $complet = $this->canal === 'transport' ? ($depart && $this->restant === 0) : ($this->typeBillet && $this->restant === 0);
        $n = 1;
        $transport = $canal === 'transport';
    @endphp

    <a href="{{ route('home') }}#evenements" class="back noprint"><x-ico name="arrow-left" size="18" /> Tous les événements</a>

    {{-- En-tête événement --}}
    <header class="evt-hero">
        <x-evenement-cover :evenement="$evt" :badge="false" :eager="true" class="evt-cover" />
        <div class="evt-info">
            <div class="kicker">{{ $evt->debut_at->locale('fr')->translatedFormat('l j F Y · H\hi') }}</div>
            <h1>{{ $evt->titre }}</h1>
            @if ($evt->accroche)
                <p class="lead">{{ $evt->accroche }}</p>
            @endif
            <ul class="facts">
                <li><x-ico name="pin" size="17" /> {{ $evt->lieu }}, {{ $evt->ville }}</li>
                @if ($depart)
                    <li><x-ico name="bus" size="17" /> Départ {{ $depart->depart_at->format('H\hi') }}@if ($depart->retour_at) · retour {{ $depart->retour_at->format('H\hi') }}@endif</li>
                @endif
            </ul>
            @if ($evt->description)
                <details class="desc">
                    <summary>À propos de l’événement</summary>
                    <p>{!! nl2br(e($evt->description)) !!}</p>
                </details>
            @endif
        </div>
    </header>

    @if (! $this->open)
        <div class="alert alert-warn"><x-ico name="info" /> Les réservations sont fermées pour le moment. Reviens un peu plus tard.</div>
    @endif

    @if ($errors->has('reservation') || $errors->has('quantite') || $errors->has('depart_id') || $errors->has('zone_id') || $errors->has('type_billet_id'))
        <div class="alert alert-err" role="alert">
            <x-ico name="alert" />
            <div>
                @foreach (['reservation', 'quantite', 'depart_id', 'zone_id', 'type_billet_id'] as $k)
                    @error($k) <div>{{ $message }}</div> @enderror
                @endforeach
            </div>
        </div>
    @endif

    @if ($this->departs->isEmpty() && $this->typesBillets->isEmpty())
        <div class="panel empty">
            <x-ico name="calendar" size="34" />
            <h3>Pas encore de départ ouvert</h3>
            <p class="hint">Les places pour cet événement ne sont pas encore en vente. Reviens bientôt.</p>
            <a href="{{ route('home') }}#evenements" class="btn btn-ghost" style="margin-top:12px">Voir d’autres événements</a>
        </div>
    @else
        <form class="booking" wire:submit="reserve">
            <div class="panel steps-panel">
                @if ($this->departs->isNotEmpty() && $this->typesBillets->isNotEmpty())
                    <div class="seg seg-full" role="tablist" aria-label="Type de réservation">
                        <button type="button" role="tab" aria-selected="{{ $transport ? 'true' : 'false' }}" class="{{ $transport ? 'on' : '' }}" wire:click="$set('canal', 'transport')"><x-ico name="bus" size="18" /> Transport</button>
                        <button type="button" role="tab" aria-selected="{{ ! $transport ? 'true' : 'false' }}" class="{{ ! $transport ? 'on' : '' }}" wire:click="$set('canal', 'billet')"><x-ico name="ticket" size="18" /> Billet d’entrée</button>
                    </div>
                @endif

                @if ($transport)
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
                        <p class="hint step-hint">Le car passe par ces arrêts, dans cet ordre. Choisis le plus proche de chez toi.</p>
                        <ol class="route" aria-label="Itinéraire">
                            @foreach ($this->tarifs as $t)
                                <li class="stop {{ $t->principal ? 'principal' : '' }}" wire:key="z-{{ $t->id }}">
                                    <input type="radio" name="zone" wire:model.live="zone_id" value="{{ $t->zone_id }}" aria-label="{{ $t->zone->nom }}, passage vers {{ $t->heure() ?? 'heure à confirmer' }}, {{ number_format($t->prix, 0, ',', ' ') }} francs">
                                    <div class="body">
                                        <span class="time">{{ $t->heure() ?? '—' }}</span>
                                        <span class="dot" aria-hidden="true"></span>
                                        <span class="name">
                                            <b>{{ $t->zone->nom }} @if ($t->principal)<span class="tag-main">Principal</span>@endif</b>
                                            @if ($t->point_rdv)<small>{{ $t->point_rdv }}</small>@endif
                                        </span>
                                        <span class="price">{{ number_format($t->prix, 0, ',', ' ') }} F</span>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                        <div class="route-end"><span></span><x-ico name="flag" size="18" /><span>Arrivée : {{ $evt->lieu }}</span></div>
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
                    <legend><span class="num">{{ $n++ }}</span> Qui voyage ?</legend>
                    <div class="qty-row">
                        <div class="stepper">
                            <button type="button" wire:click="decrement" aria-label="Retirer un passager" @disabled($quantite <= 1)><x-ico name="minus" /></button>
                            <output aria-live="polite">{{ $quantite }}</output>
                            <button type="button" wire:click="increment" aria-label="Ajouter un passager" @disabled($quantite >= $max)><x-ico name="plus" /></button>
                        </div>
                        <span class="hint" style="margin:0">
                            @if ($complet)
                                <strong style="color:var(--err)">Complet</strong>
                            @else
                                {{ $quantite }} passager{{ $quantite > 1 ? 's' : '' }} · max {{ $this->maxParCommande }} par réservation · {{ $this->restant }} place{{ $this->restant > 1 ? 's' : '' }} libre{{ $this->restant > 1 ? 's' : '' }}
                            @endif
                        </span>
                    </div>

                    <ol class="pax-list">
                        @foreach ($passagers as $i => $nomPax)
                            <li wire:key="pax-{{ $i }}">
                                <label class="field">
                                    <span>{{ $i === 0 ? 'Passager 1 · toi' : 'Passager '.($i + 1).' · invité' }}</span>
                                    <span class="pax-input">
                                        <input type="text" wire:model.blur="passagers.{{ $i }}" maxlength="120" autocomplete="{{ $i === 0 ? 'name' : 'off' }}"
                                               placeholder="{{ $i === 0 ? ($this->nomPayeur() ?: 'Ton nom complet') : 'Nom et prénom' }}">
                                        @if ($i > 0)
                                            <button type="button" class="pax-del" wire:click="retirerPassager({{ $i }})" aria-label="Retirer le passager {{ $i + 1 }}"><x-ico name="x" size="18" /></button>
                                        @endif
                                    </span>
                                </label>
                                @error('passagers.'.$i) <span class="err">{{ $message }}</span> @enderror
                            </li>
                        @endforeach
                    </ol>
                    @if ($quantite < $max)
                        <button type="button" class="add-pax" wire:click="increment"><x-ico name="plus" size="18" /> Ajouter un passager</button>
                    @endif
                    <p class="hint"><x-ico name="info" size="15" style="vertical-align:-2px" /> Tes invités n’ont pas besoin de compte. Chacun reçoit son propre billet QR{{ $transport ? ', valable pour l’aller et le retour' : '' }}.</p>
                </fieldset>

                @guest
                    <div id="inscription" x-ref="ident" @class(['ident', 'attention' => $errors->hasAny(['nom', 'tel', 'email', 'password'])])
                         x-data="{ secoue: false }"
                         x-on:attirer-inscription.window="secoue = false; $nextTick(() => { $el.scrollIntoView({ behavior: 'smooth', block: 'center' }); secoue = true; setTimeout(() => secoue = false, 1300); const i = $el.querySelector('input'); if (i) setTimeout(() => i.focus({ preventScroll: true }), 500); })"
                         :class="{ 'shake': secoue }">
                        <div class="ident-banner">
                            <x-ico name="user" size="20" />
                            <div><b>Dernière étape : ton compte</b><span>30 secondes, pour recevoir tes billets et les retrouver à tout moment.</span></div>
                        </div>
                        <fieldset>
                            <legend><span class="num">{{ $n++ }}</span> Ton compte</legend>
                            <div class="seg" role="tablist">
                                <button type="button" role="tab" class="{{ $mode === 'inscription' ? 'on' : '' }}" wire:click="$set('mode', 'inscription')">Je suis nouveau</button>
                                <button type="button" role="tab" class="{{ $mode === 'connexion' ? 'on' : '' }}" wire:click="$set('mode', 'connexion')">J’ai déjà un compte</button>
                            </div>

                            @if ($mode === 'inscription')
                                <p class="hint" style="margin:0 0 14px">Un compte est nécessaire pour retrouver tes billets. Il est créé en même temps que ta réservation.</p>
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
                <div class="recap panel">
                    <h3 class="recap-title">Ton trajet</h3>
                    @if ($transport && $depart)
                        <ol class="legs-list">
                            <li>
                                <span class="leg-tag">Aller</span>
                                <div>
                                    <b>{{ $depart->depart_at->locale('fr')->translatedFormat('D j M') }} · {{ $tarif?->heure() ?? $depart->depart_at->format('H\hi') }}</b>
                                    <small>{{ $tarif?->zone?->nom ?? 'Ton arrêt' }}{{ $tarif?->point_rdv ? ' ('.$tarif->point_rdv.')' : '' }} → {{ $evt->lieu }}</small>
                                </div>
                            </li>
                            <li>
                                <span class="leg-tag ret">Retour</span>
                                <div>
                                    <b>{{ $depart->retour_at ? $depart->retour_at->locale('fr')->translatedFormat('D j M · H\hi') : 'Après l’événement' }}</b>
                                    <small>{{ $evt->lieu }} → {{ $tarif?->zone?->nom ?? 'ton arrêt' }}</small>
                                </div>
                            </li>
                        </ol>
                    @elseif (! $transport)
                        <p class="hint" style="margin:0 0 8px">{{ $this->typeBillet?->nom }} · {{ $evt->debut_at->locale('fr')->translatedFormat('D j M · H\hi') }}</p>
                    @endif

                    <div class="recap-lines">
                        <div><span>{{ $quantite }} × {{ number_format($prix, 0, ',', ' ') }} F</span><span>{{ number_format($prix * $quantite, 0, ',', ' ') }} F</span></div>
                        <div class="recap-total"><span>Total</span><span>{{ number_format($prix * $quantite, 0, ',', ' ') }} FCFA</span></div>
                    </div>

                    <ul class="reassure">
                        <li><x-ico name="wallet" size="17" /> Paiement Mobile Money (Wave)</li>
                        <li><x-ico name="qr" size="17" /> Un billet QR par passager</li>
                        <li><x-ico name="clock" size="17" /> Places bloquées {{ (int) \App\Models\Setting::get('delai_paiement_min', 30) }} min pour payer</li>
                    </ul>
                </div>

                <div class="bar">
                    <button class="cta" type="submit" @disabled(! $this->open || $prix < 1 || $complet) wire:loading.attr="disabled" wire:target="reserve"
                        @guest
                            x-on:click="
                                const bloc = document.getElementById('inscription');
                                const champs = bloc ? [...bloc.querySelectorAll('input')].filter(i => i.offsetParent !== null) : [];
                                if (champs.some(i => ! i.value.trim())) { $event.preventDefault(); window.dispatchEvent(new CustomEvent('attirer-inscription')); }
                            "
                        @endguest>
                        <span wire:loading.remove wire:target="reserve">{{ $complet ? 'Complet' : (auth()->check() ? 'Réserver et payer' : 'Créer mon compte et réserver') }}</span>
                        <span wire:loading wire:target="reserve">Un instant…</span>
                        <span class="amt">{{ number_format($prix * $quantite, 0, ',', ' ') }} F</span>
                    </button>
                </div>
            </aside>
        </form>
    @endif

    <style>
        .evt-hero { display: grid; gap: 20px; margin-bottom: 26px; }
        .evt-cover { border-radius: var(--r-lg); box-shadow: var(--shadow); }
        @media (min-width: 900px) { .evt-hero { grid-template-columns: 1.05fr 1fr; align-items: center; gap: 36px; } }
        .evt-info h1 { color: var(--fg); }
        .lead { color: var(--muted); font-size: 1.08rem; margin: 0 0 14px; }
        .facts { list-style: none; padding: 0; margin: 0; display: flex; flex-wrap: wrap; gap: 8px; }
        .facts li { display: inline-flex; align-items: center; gap: 7px; background: #fff; border: 1px solid var(--line); padding: 7px 12px; border-radius: 99px; font-size: 14.5px; font-weight: 500; }
        .facts .ico { color: var(--brand); }
        .desc { margin-top: 14px; }
        .desc summary { cursor: pointer; font-weight: 600; color: var(--brand); min-height: 32px; }
        .desc p { color: var(--muted); }
        .booking { display: grid; gap: 20px; align-items: start; }
        @media (min-width: 960px) { .booking { grid-template-columns: 1.5fr 1fr; } .side { position: sticky; top: 92px; } .side .bar { position: static; background: none; padding: 14px 0 0; } }
        .seg { display: inline-flex; background: var(--soft); border: 1px solid var(--line); border-radius: 12px; padding: 4px; margin-bottom: 14px; gap: 4px; }
        .seg button { border: 0; background: none; padding: 9px 14px; border-radius: 9px; font: 500 15px var(--body); cursor: pointer; color: var(--muted); min-height: 42px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
        .seg button.on { background: #fff; color: var(--brand); font-weight: 600; box-shadow: var(--shadow-sm); }
        .seg-full { display: grid; grid-template-columns: 1fr 1fr; width: 100%; margin-bottom: 24px; }
        .step-hint { margin: -4px 0 12px; }
        .qty-row { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 16px; }
        .pax-list { list-style: none; padding: 0; margin: 0; display: grid; gap: 2px; }
        .pax-list .field { margin-bottom: 10px; }
        .pax-list .field > span:first-child { color: var(--muted); font-size: 13.5px; }
        .pax-input { display: flex; gap: 8px; }
        .pax-del { flex: none; width: 52px; border: 1.5px solid var(--line); border-radius: 12px; background: #fff; color: var(--muted); cursor: pointer; display: grid; place-items: center; }
        .pax-del:hover { color: var(--err); border-color: var(--err); }
        .add-pax { display: inline-flex; align-items: center; gap: 8px; border: 1.5px dashed #b9c6d6; background: none; color: var(--brand); font: 600 15px var(--body); border-radius: 12px; padding: 10px 16px; min-height: 46px; cursor: pointer; width: 100%; justify-content: center; }
        .add-pax:hover { border-color: var(--brand); background: var(--brand-soft); }
.ident { margin-top: 4px; padding: 20px; border: 2px solid var(--accent); border-radius: var(--r); background: linear-gradient(#fff, #fff) padding-box; box-shadow: 0 0 0 6px var(--accent-soft); transition: box-shadow .3s; }
        .ident-banner { display: flex; gap: 12px; align-items: flex-start; background: var(--accent-soft); color: var(--accent-dark); border-radius: 12px; padding: 12px 14px; margin-bottom: 16px; }
        .ident-banner b { display: block; color: var(--fg); }
        .ident-banner span { font-size: 14px; color: var(--muted); }
        .ident.attention { animation: pulse-ring 1.6s ease-out 2; }
        .ident.shake { animation: shake .6s cubic-bezier(.36,.07,.19,.97) both, pulse-ring 1.2s ease-out; }
        @keyframes shake { 10%, 90% { transform: translateX(-2px); } 20%, 80% { transform: translateX(4px); } 30%, 50%, 70% { transform: translateX(-8px); } 40%, 60% { transform: translateX(8px); } }
        @keyframes pulse-ring { 0% { box-shadow: 0 0 0 0 rgba(251,114,18,.55); } 100% { box-shadow: 0 0 0 18px rgba(251,114,18,0); } }
        .ident fieldset { margin-bottom: 0; }
        .recap-title { color: var(--brand); margin-bottom: 14px; }
        .legs-list { list-style: none; margin: 0 0 16px; padding: 0; display: grid; gap: 12px; }
        .legs-list li { display: flex; gap: 12px; align-items: flex-start; }
        .legs-list b { display: block; text-transform: capitalize; }
        .legs-list small { color: var(--muted); font-size: 14px; }
        .leg-tag { flex: none; font: 600 12px var(--body); text-transform: uppercase; letter-spacing: .05em; background: var(--brand); color: #fff; border-radius: 8px; padding: 4px 8px; min-width: 62px; text-align: center; }
        .leg-tag.ret { background: var(--accent); }
        .recap-lines { border-top: 1px solid var(--line); padding-top: 12px; display: grid; gap: 6px; }
        .recap-lines > div { display: flex; justify-content: space-between; color: var(--muted); }
        .recap-total { color: var(--fg) !important; font-weight: 700; font-size: 1.2rem; padding-top: 6px; }
        .recap-total span:last-child { color: var(--brand); }
        .reassure { list-style: none; padding: 14px 0 0; margin: 14px 0 0; border-top: 1px solid var(--line); display: grid; gap: 8px; color: var(--muted); font-size: 14.5px; }
        .reassure li { display: flex; gap: 8px; align-items: center; }
        .reassure .ico { color: var(--ok); }
        @media (max-width: 959px) {
            .side .bar { position: fixed; left: 0; right: 0; bottom: calc(var(--tabbar-h) + env(safe-area-inset-bottom)); padding: 10px 16px 12px; background: linear-gradient(transparent, var(--bg) 26%); z-index: 30; }
            .evt-page { padding-bottom: 80px; }
        }
    </style>
</div>
