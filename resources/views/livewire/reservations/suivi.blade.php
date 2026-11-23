<div class="narrow suivi">
    @php
        $r = $this->reservation;
        $etape = $this->etape;
        $evt = $r->depart?->evenement ?? $r->typeBillet?->evenement;
        $ordre = ['payer' => 1, 'attendre' => 2, 'billets' => 3, 'close' => 0];
        $pos = $ordre[$etape];
    @endphp

    <a href="{{ route('reservations.index') }}" class="back noprint"><x-ico name="arrow-left" size="18" /> Mes réservations</a>

    @if ($etape !== 'close')
        <ol class="progress noprint" aria-label="Avancement">
            <li class="done">Réservé</li>
            <li @class(['on' => $pos === 1, 'done' => $pos > 1])>Paiement</li>
            <li @class(['on' => $pos === 2, 'done' => $pos > 2])>Vérification</li>
            <li @class(['on' => $pos === 3, 'done' => $pos === 3])>Billets</li>
        </ol>
    @endif

    <header class="hero noprint">
        @if ($etape === 'payer')
            <h1>Paie pour confirmer tes places</h1>
            <p>Deux étapes : tu paies sur Wave, puis tu nous envoies la capture du reçu.</p>
        @elseif ($etape === 'attendre')
            <h1>Paiement en vérification</h1>
            <p>Notre équipe vérifie ton reçu. Tes billets s’affichent ici automatiquement, et tu les reçois par e-mail.</p>
        @elseif ($etape === 'billets')
            <h1>C’est validé, bon voyage !</h1>
            <p>Un QR code par passager. Il est contrôlé à la montée de l’aller, puis à celle du retour.</p>
        @else
            <h1>Réservation {{ strtolower($r->statut->getLabel()) }}</h1>
            <p>Cette réservation n’est plus active.</p>
        @endif
    </header>

    {{-- Résumé : trajet, passagers, montant --}}
    <section class="panel resume noprint">
        <div class="rhead">
            <div>
                <div class="mono muted">{{ $r->ref }}</div>
                <h2 style="margin:2px 0 0">{{ $evt?->titre }}</h2>
                @if ($r->type !== \App\Enums\ReservationType::Transport)
                    <div class="hint" style="margin:2px 0 0">{{ $r->typeBillet?->nom }}</div>
                @endif
            </div>
            <span class="badge {{ $r->statut->value }}">{{ $r->statut->getLabel() }}</span>
        </div>

        @if ($r->type === \App\Enums\ReservationType::Transport)
            <x-trajet :reservation="$r" class="resume-trajet" />
        @endif

        <div class="rbody">
            <div>
                <span class="k">{{ $r->quantite }} passager{{ $r->quantite > 1 ? 's' : '' }}</span>
                <ul class="pax-names">
                    @foreach ($r->nomsPassagers() as $nomPax)
                        <li><x-ico name="user" size="15" /> {{ $nomPax }}</li>
                    @endforeach
                </ul>
            </div>
            <div class="rtotal">
                <span class="k">Total</span>
                <strong>{{ number_format($r->montant, 0, ',', ' ') }} FCFA</strong>
                <small class="muted">{{ $r->quantite }} × {{ number_format($r->prix_unitaire, 0, ',', ' ') }} F</small>
            </div>
        </div>
    </section>


    @if ($etape === 'payer')
        @php $refus = $r->paiements->first(fn ($p) => $p->statut === \App\Enums\PaiementStatus::Refuse); @endphp
        @if ($refus?->motif_refus)
            <div class="alert alert-err" role="alert"><div><strong>Reçu refusé :</strong> {{ $refus->motif_refus }}<br>Envoie un reçu valide avant la fin du délai.</div></div>
        @endif

        @if ($r->expire_at)
            <div class="countdown" x-data="{ fin: {{ $r->expire_at->getTimestamp() * 1000 }}, reste: '', urgent: false, t: null,
                    tick() { const s = Math.max(0, Math.floor((this.fin - Date.now()) / 1000)); this.reste = String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0'); this.urgent = s < 300; if (s === 0) { clearInterval(this.t); $wire.$refresh(); } } }"
                 x-init="tick(); t = setInterval(() => tick(), 1000)" :class="{ urgent }">
                <x-ico name="clock" size="18" style="vertical-align:-3px" /> Places bloquées encore <strong class="mono" x-text="reste"></strong> (jusqu’à {{ $r->expire_at->format('H\hi') }})
            </div>
        @endif

        <section class="panel paystep">
            <div class="ps-head"><span class="num">1</span><h3>Paie {{ number_format($r->montant, 0, ',', ' ') }} FCFA sur Wave</h3></div>
            @if ($lien = $this->lienWave())
                <a class="cta wave" href="{{ $lien }}" target="_blank" rel="noopener">
                    <span>Ouvrir Wave et payer</span>
                    <span class="amt">{{ number_format($r->montant, 0, ',', ' ') }} F</span>
                </a>
                <p class="hint">Une fois le paiement fait, fais une capture d’écran du reçu et reviens ici.</p>
            @else
                <div class="alert alert-warn">Le lien de paiement n’est pas encore configuré. Contacte l’organisation.</div>
            @endif
        </section>

        <form class="panel paystep" wire:submit="submitProof"
              x-data="preuvePaiement({ attendu: {{ (int) $r->montant }}, prix: {{ (int) $r->prix_unitaire }} })">
            <div class="ps-head"><span class="num">2</span><h3>Envoie la capture du reçu</h3></div>

            <label class="drop" :class="{ filled: apercu }">
                <input type="file" accept="image/*" x-ref="file" @change="choisir($event)">
                <span class="drop-empty" x-show="!apercu">
                    <span class="drop-ico" aria-hidden="true"><x-ico name="camera" size="28" /></span>
                    <b>Ajouter la capture du reçu Wave</b>
                    <small>Le montant et l’ID de transaction sont lus automatiquement</small>
                </span>
                <span class="drop-filled" x-show="apercu" x-cloak>
                    <img :src="apercu" alt="Aperçu du reçu">
                    <span>
                        <b x-text="envoi ? 'Envoi… ' + progress + '%' : 'Capture ajoutée ✓'"></b>
                        <small>Touche pour changer d’image</small>
                    </span>
                </span>
            </label>
            <div class="barre" x-show="envoi" x-cloak><span :style="'width:' + progress + '%'"></span></div>
            @error('capture') <span class="err">{{ $message }}</span> @enderror

            {{-- Lecture automatique --}}
            <div class="ocr" x-show="etat !== 'vide'" x-cloak aria-live="polite">
                <div class="ocr-run" x-show="etat === 'lecture'">
                    <span class="spin" aria-hidden="true"></span>
                    <span>Lecture du reçu… <strong x-text="lecturePct + '%'"></strong></span>
                </div>
                <div class="ocr-ok" x-show="etat === 'lu'"><x-ico name="check-circle" size="18" /> Reçu lu automatiquement. Vérifie les informations ci-dessous.</div>
                <div class="ocr-bad" x-show="etat === 'ecart'" role="alert">
                    <x-ico name="alert" size="18" /> <span x-text="message"></span>
                    <button type="button" class="linklike" @click="reinitialiser()">Changer de capture</button>
                </div>
                <div class="ocr-warn" x-show="etat === 'partiel' || etat === 'echec'">
                    <x-ico name="info" size="18" /> <span x-text="message"></span>
                    <button type="button" class="linklike" @click="relancer()" x-show="etat === 'echec'">Relancer la lecture</button>
                </div>
            </div>

            <div class="lu-grid">
                <label class="field">
                    <span>Montant payé (FCFA) <em class="lu-tag" x-show="montantLu" x-cloak>lu sur le reçu</em></span>
                    <input type="number" inputmode="numeric" min="1" step="1" wire:model="montant_declare"
                           :readonly="montantLu" :class="{ 'is-lu': montantLu }" :aria-readonly="montantLu ? 'true' : 'false'">
                    @error('montant_declare') <span class="err">{{ $message }}</span> @enderror
                </label>
                <label class="field">
                    <span>ID de transaction <em class="lu-tag" x-show="idLu" x-cloak>lu sur le reçu</em></span>
                    <input type="text" wire:model="transaction_id" placeholder="Ex. T_ABCD1234EFGH" autocomplete="off" autocapitalize="characters" spellcheck="false"
                           :readonly="idLu" :class="{ 'is-lu': idLu }" :aria-readonly="idLu ? 'true' : 'false'">
                    @error('transaction_id') <span class="err">{{ $message }}</span> @enderror
                </label>
            </div>
            <p class="hint" style="margin-top:0">Montant exact à payer : <strong>{{ number_format($r->montant, 0, ',', ' ') }} FCFA</strong>. La preuve n’est acceptée que si le reçu indique ce montant.</p>

            <button class="cta center" type="submit" style="margin-top:12px" wire:loading.attr="disabled" wire:target="submitProof,capture" x-bind:disabled="envoi || etat === 'lecture' || etat === 'ecart' || (etat !== 'vide' && ! montantOk)">
                <span wire:loading.remove wire:target="submitProof" x-text="etat === 'lecture' ? 'Lecture du reçu…' : (etat === 'ecart' ? 'Montant incorrect' : 'Envoyer la preuve')">Envoyer la preuve</span>
                <span wire:loading wire:target="submitProof">Envoi en cours…</span>
            </button>
        </form>

        <p class="hint noprint" style="text-align:center">
            Tu as changé d’avis ?
            <button type="button" class="linklike" wire:click="annuler" wire:confirm="Annuler cette réservation et libérer les places ?">Annuler la réservation</button>
        </p>
    @elseif ($etape === 'attendre')
        <section class="panel wait" wire:poll.8s.visible>
            <div class="spinner" aria-hidden="true"></div>
            <div>
                <h3>Reçu bien reçu ✓</h3>
                <p class="hint" style="margin:0">En général, la vérification prend quelques minutes. Tu peux fermer cette page : on t’envoie tes billets par e-mail à <strong>{{ auth()->user()->email }}</strong>.</p>
            </div>
        </section>
    @elseif ($etape === 'billets')
        <div class="actions noprint">
            <a class="btn btn-ghost" href="{{ route('reservations.imprimer', $r) }}" target="_blank"><x-ico name="printer" size="18" /> Imprimer / PDF</a>
            @if ($r->depart)
                @php $arretRdv = $r->depart->tarifs()->where('zone_id', $r->zone_id)->first(); @endphp
                <a class="btn btn-ghost" href="{{ route('reservations.agenda', $r) }}"><x-ico name="calendar-plus" size="18" /> Ajouter à mon agenda</a>
                <a class="btn btn-ghost" target="_blank" rel="noopener"
                   href="https://www.google.com/maps/search/?api=1&query={{ rawurlencode(trim(($arretRdv?->point_rdv ?: $r->zone?->nom).', Abidjan')) }}"><x-ico name="navigation" size="18" /> Itinéraire vers l’arrêt</a>
            @endif
            @if ($evt?->lien_whatsapp)
                <a class="btn btn-wa" href="{{ $evt->lien_whatsapp }}" target="_blank" rel="noopener"><x-ico name="chat" size="18" /> Rejoindre le groupe WhatsApp</a>
            @endif
        </div>

        <div class="billets">
            @foreach ($r->billets as $billet)
                <x-billet :billet="$billet" :reservation="$r" wire:key="b-{{ $billet->id }}" />
            @endforeach
        </div>

        @if ($evt)
            <div class="panel noprint invite">
                <h3>Tes amis y vont aussi ?</h3>
                <p class="hint" style="margin:0">Partage l’événement : ils réservent leur place dans le même car.</p>
                <x-partage :url="route('evenements.show', $evt)" titre=""
                    :texte="'J’ai réservé mon aller-retour pour '.$evt->titre.' avec CotaTrans 🚌 Réserve ta place :'" />
            </div>
        @endif
        @if ($evt?->lien_whatsapp)
            <p class="hint noprint" style="text-align:center;margin-top:16px">Le groupe WhatsApp est facultatif : infos de dernière minute et prochains événements.</p>
        @endif
    @else
        <div class="panel" style="text-align:center">
            <p>Les places ont été libérées.</p>
            @if ($evt)
                <a class="btn" href="{{ route('evenements.show', $evt) }}">Réserver à nouveau</a>
            @endif
        </div>
    @endif

    <style>
        .back { display: inline-block; color: var(--muted); text-decoration: none; font-weight: 600; font-size: 14px; margin-bottom: 14px; }
        .progress { display: flex; list-style: none; padding: 0; margin: 0 0 18px; gap: 4px; }
        .progress li { flex: 1; text-align: center; font: 600 11px var(--body); text-transform: uppercase; letter-spacing: .04em; color: var(--muted); padding-top: 10px; border-top: 4px solid var(--line); }
        .progress li.done { color: var(--brand); border-color: var(--brand); }
        .progress li.on { color: var(--accent-dark); border-color: var(--accent); }
        .resume { margin-bottom: 16px; }
        .resume h2 { font-size: 1.3rem; }
        .rhead { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
        .rbody { display: flex; justify-content: space-between; gap: 16px; margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--line); flex-wrap: wrap; }
        .resume-trajet { margin-top: 16px; padding: 14px; background: var(--soft); border-radius: 12px; }
        .pax-names { list-style: none; padding: 0; margin: 4px 0 0; display: grid; gap: 4px; }
        .pax-names li { display: flex; gap: 6px; align-items: center; font-weight: 500; }
        .pax-names .ico { color: var(--brand); }
        .rtotal { text-align: right; display: grid; }
        .rtotal strong { font: 700 1.35rem var(--display); color: var(--brand); }
        .rbody .k { display: block; color: var(--muted); font-size: 12px; text-transform: uppercase; letter-spacing: .04em; }
        .countdown { background: var(--warn-soft); border: 1px solid #f3d39b; color: var(--warn); border-radius: 14px; padding: 10px 14px; margin-bottom: 16px; font-size: 14px; }
        .countdown.urgent { background: var(--err-soft); border-color: #f4b8b3; color: var(--err); }
        .paystep { margin-bottom: 16px; }
        .ps-head { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
        .ps-head h3 { margin: 0; }
        .ps-head .num { width: 28px; height: 28px; border-radius: 50%; background: var(--brand); color: #fff; display: grid; place-items: center; font: 600 14px var(--body); flex: none; }
        .cta.wave { background: #1dc8ff; color: #04263a; }
        .cta.wave:hover { background: #0bb4ea; }
        .drop { position: relative; display: block; border: 2px dashed var(--line); border-radius: 16px; background: var(--soft); cursor: pointer; transition: border-color .15s; }
        .drop:hover, .drop:focus-within { border-color: var(--accent); }
        .drop.filled { border-style: solid; border-color: var(--ok); background: var(--ok-soft); }
        .drop input { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
        .drop-empty { display: grid; justify-items: center; gap: 4px; padding: 26px 16px; text-align: center; }
        .drop-empty .drop-ico { color: var(--brand); width: 56px; height: 56px; border-radius: 50%; background: var(--brand-soft); display: grid; place-items: center; margin-bottom: 4px; }
        .drop-empty small, .drop-filled small { color: var(--muted); }
        .drop-filled { display: flex; align-items: center; gap: 14px; padding: 12px; }
        .drop-filled img { width: 64px; height: 84px; object-fit: cover; border-radius: 10px; border: 1px solid var(--line); }
        .drop-filled b { display: block; color: var(--ok); }
        .barre { margin-top: 8px; }
        .ocr { margin-top: 12px; border-radius: 12px; font-size: 14.5px; }
        .ocr > div { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; padding: 10px 12px; border-radius: 12px; }
        .ocr-run { background: var(--brand-soft); color: var(--brand); }
        .ocr-ok { background: var(--ok-soft); color: var(--ok); }
        .ocr-warn { background: var(--warn-soft); color: var(--warn); }
        .ocr-bad { background: #fdecec; color: #b42318; font-weight: 600; }
        .spin { width: 16px; height: 16px; border-radius: 50%; border: 2.5px solid currentColor; border-right-color: transparent; animation: spin .8s linear infinite; }
        .lu-grid { display: grid; gap: 0 14px; margin-top: 14px; }
        @media (min-width: 520px) { .lu-grid { grid-template-columns: 1fr 1.3fr; } }
        .lu-tag { font-style: normal; font: 600 11.5px var(--body); background: var(--ok-soft); color: var(--ok); border-radius: 99px; padding: 2px 8px; margin-left: 6px; }
        .field input.is-lu { background: var(--soft); border-color: #b7e2cf; color: var(--fg); font-weight: 600; cursor: default; }
        .wait { display: flex; gap: 16px; align-items: center; }
        .spinner { width: 36px; height: 36px; border-radius: 50%; border: 4px solid var(--line); border-top-color: var(--accent); animation: spin 1s linear infinite; flex: none; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; }
        .billets { display: grid; gap: 18px; }
        .invite { margin-top: 18px; text-align: center; }
        .invite .partage { justify-content: center; }
    </style>
</div>
