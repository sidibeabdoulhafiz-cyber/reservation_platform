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
              x-data="{
                  apercu: null, progress: 0, envoi: false,
                  async choisir(e) {
                      const f = e.target.files?.[0]; if (!f) return;
                      this.apercu = URL.createObjectURL(f); this.envoi = true; this.progress = 0;
                      let fichier = f;
                      try {
                          const img = await createImageBitmap(f);
                          const s = Math.min(1, 1280 / Math.max(img.width, img.height));
                          const c = document.createElement('canvas'); c.width = Math.round(img.width * s); c.height = Math.round(img.height * s);
                          c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
                          const blob = await new Promise(r => c.toBlob(r, 'image/jpeg', 0.82));
                          if (blob) fichier = new File([blob], 'recu.jpg', { type: 'image/jpeg' });
                      } catch (_) {}
                      $wire.upload('capture', fichier, () => { this.envoi = false; this.progress = 100 }, () => { this.envoi = false }, (ev) => { this.progress = ev.detail.progress });
                  },
                  retirer() { this.apercu = null; this.progress = 0; $wire.set('capture', null); this.$refs.file.value = '' }
              }">
            <div class="ps-head"><span class="num">2</span><h3>Envoie la preuve de paiement</h3></div>

            <label class="drop" :class="{ filled: apercu }">
                <input type="file" accept="image/*" x-ref="file" @change="choisir($event)">
                <template x-if="!apercu">
                    <span class="drop-empty">
                        <span class="drop-ico" aria-hidden="true"><x-ico name="camera" size="28" /></span>
                        <b>Ajouter la capture du reçu</b>
                        <small>Touche ici pour choisir dans ta galerie</small>
                    </span>
                </template>
                <template x-if="apercu">
                    <span class="drop-filled">
                        <img :src="apercu" alt="Aperçu du reçu">
                        <span>
                            <b x-text="envoi ? 'Envoi… ' + progress + '%' : 'Capture prête ✓'"></b>
                            <small>Touche pour changer</small>
                        </span>
                    </span>
                </template>
            </label>
            <div class="barre" x-show="envoi" x-cloak><span :style="'width:' + progress + '%'"></span></div>
            @error('capture') <span class="err">{{ $message }}</span> @enderror

            <label class="field" style="margin-top:14px">
                ID de transaction Wave
                <input type="text" wire:model="transaction_id" placeholder="Ex. T_ABCD1234EFGH" autocomplete="off" autocapitalize="characters" spellcheck="false">
                <small class="hint" style="font-weight:400;margin:0">Il apparaît sur le reçu Wave, sous le montant.</small>
                @error('transaction_id') <span class="err">{{ $message }}</span> @enderror
            </label>

            <details class="montant" @if ($errors->has('montant_declare')) open @endif>
                <summary>Montant payé : <strong class="mono">{{ number_format((int) $montant_declare, 0, ',', ' ') }} F</strong> · modifier si différent</summary>
                <label class="field" style="margin-top:10px">
                    Montant exact lu sur le reçu (FCFA)
                    <input type="number" inputmode="numeric" min="1" step="1" wire:model="montant_declare">
                </label>
            </details>
            @error('montant_declare') <span class="err">{{ $message }}</span> @enderror

            <button class="cta center" type="submit" style="margin-top:16px" wire:loading.attr="disabled" wire:target="submitProof,capture" x-bind:disabled="envoi">
                <span wire:loading.remove wire:target="submitProof">Envoyer la preuve</span>
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
            @if ($evt?->lien_whatsapp)
                <a class="btn btn-wa" href="{{ $evt->lien_whatsapp }}" target="_blank" rel="noopener"><x-ico name="chat" size="18" /> Rejoindre le groupe WhatsApp</a>
            @endif
        </div>

        <div class="billets">
            @foreach ($r->billets as $billet)
                <x-billet :billet="$billet" :reservation="$r" wire:key="b-{{ $billet->id }}" />
            @endforeach
        </div>

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
        .montant summary { cursor: pointer; font-size: 14px; color: var(--muted); }
        .wait { display: flex; gap: 16px; align-items: center; }
        .spinner { width: 36px; height: 36px; border-radius: 50%; border: 4px solid var(--line); border-top-color: var(--accent); animation: spin 1s linear infinite; flex: none; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; }
        .billets { display: grid; gap: 18px; }
    </style>
</div>
