<div>
    @php $etape = $this->etape; @endphp

    <ol class="steps" aria-label="Parcours">
        <li class="{{ $etape === 'choisir' ? 'on' : '' }}">Choisir</li>
        <li class="{{ in_array($etape, ['payer', 'attendre'], true) ? 'on' : '' }}">Payer</li>
        <li class="{{ $etape === 'billets' ? 'on' : '' }}">Billet</li>
    </ol>

    <header class="hero">
        @if ($etape === 'payer')
            <h1>Payez, puis envoyez le reçu</h1>
            <p>Ouvrez Wave, revenez ici, et déposez la capture. Votre billet s’affiche sur cet écran.</p>
        @elseif ($etape === 'attendre')
            <h1>Reçu en vérification</h1>
            <p>Cette page se met à jour toute seule. Gardez l’onglet ouvert.</p>
        @elseif ($etape === 'billets')
            <h1>Vos billets</h1>
            <p>Un numéro et un QR code par place. Présentez-les à l’entrée.</p>
        @else
            <h1>Réservez votre place</h1>
            <p>Choisissez, payez sur Wave, envoyez le reçu. Moins de deux minutes, depuis le téléphone.</p>
        @endif
    </header>

    @if (! $this->open)
        <div class="alert alert-warn">Les réservations sont fermées pour le moment.</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-err" role="alert">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @php $commande = $this->reservation; @endphp

    @if ($commande && $etape !== 'choisir')
        <section id="commande" class="panel commande" @if ($etape === 'attendre') wire:poll.10s.visible @endif>
            <div class="rhead">
                <div>
                    <div class="mono">{{ $commande->ref }}</div>
                    <div class="hint">{{ $commande->libelleOffre() }}</div>
                </div>
                <span class="badge {{ $commande->statut->value }}">{{ $commande->statut->getLabel() }}</span>
            </div>

            <div class="rbody">
                <div><span class="k">Places</span>{{ $commande->quantite }}</div>
                <div><span class="k">Prix unitaire</span><span class="mono">{{ number_format($commande->prix_unitaire, 0, ',', ' ') }} FCFA</span></div>
                <div><span class="k">Total</span><span class="mono">{{ number_format($commande->montant, 0, ',', ' ') }} FCFA</span></div>
                @if ($commande->statut === \App\Enums\ReservationStatus::APayer && $commande->expire_at)
                    <div><span class="k">Payer avant</span>{{ $commande->expire_at->format('H:i') }}</div>
                @endif
            </div>

            @if ($etape === 'payer')
                @php $refus = $commande->paiements->first(fn ($p) => $p->statut->value === 'refuse'); @endphp
                @if ($refus?->motif_refus)
                    <div class="alert alert-err">Reçu refusé : {{ $refus->motif_refus }}. Envoyez un reçu valide avant la fin du nouveau délai.</div>
                @endif

                <p>Payez un multiple de {{ number_format($commande->prix_unitaire, 0, ',', ' ') }} FCFA, puis revenez saisir le reçu.</p>

                @if ($this->lienWave())
                    <a class="cta" href="{{ $this->lienWave() }}" target="_blank" rel="noopener">
                        <span>Payer {{ number_format($commande->montant, 0, ',', ' ') }} FCFA sur Wave</span>
                    </a>
                @else
                    <div class="alert alert-warn">Le lien Wave n’est pas encore configuré. Contactez l’organisation.</div>
                @endif

                <form class="proof" wire:submit="submitProof">
                    <label>
                        Montant lu sur le reçu (FCFA)
                        <input type="number" inputmode="numeric" min="1" step="1" wire:model="montant_declare" autocomplete="off">
                    </label>

                    <label>
                        Identifiant de transaction Wave
                        <input type="text" wire:model="transaction_id" placeholder="Ex. T_XXXXXXXX" autocomplete="off" autocapitalize="characters" spellcheck="false">
                    </label>

                    <label class="drop">
                        <input type="file" accept="image/*" x-ref="file" @change="compresserRecu($event, $wire)">
                        @if ($capture)
                            <span>Capture prête à envoyer</span>
                        @else
                            <span>Ajouter la capture du reçu</span>
                        @endif
                    </label>
                    @if ($uploadProgress > 0 && $uploadProgress < 100)
                        <div class="barre" role="progressbar" aria-valuenow="{{ $uploadProgress }}" aria-valuemin="0" aria-valuemax="100">
                            <span style="width: {{ $uploadProgress }}%"></span>
                        </div>
                    @endif

                    <button class="cta" type="submit" wire:loading.attr="disabled" wire:target="submitProof">
                        <span wire:loading.remove wire:target="submitProof">Envoyer le reçu</span>
                        <span wire:loading wire:target="submitProof">Envoi…</span>
                    </button>
                </form>
            @elseif ($etape === 'attendre')
                <p class="note">Reçu reçu. Dès qu’un administrateur confirme, vos billets apparaissent ici.</p>
            @elseif ($etape === 'billets')
                <p class="note note-ok">Paiement confirmé. Voici {{ $commande->billets->count() }} billet(s).</p>
                <div class="billets">
                    @foreach ($commande->billets as $billet)
                        <x-billet :billet="$billet" />
                    @endforeach
                </div>
                <button class="cta" type="button" wire:click="nouvelle" style="margin-top:16px">
                    <span>Nouvelle réservation</span>
                </button>
            @endif
        </section>
    @endif

    @if ($etape === 'choisir')
        @if ($this->evenements->isEmpty())
            <div class="alert">Aucun événement n’est ouvert à la réservation pour le moment.</div>
        @else
            <form class="panel" wire:submit="reserve" x-data="{ q: @entangle('quantite'), prix: {{ $this->prixUnitaire }} }" x-effect="prix = {{ $this->prixUnitaire }}">
                <div class="canaux" role="tablist">
                    <button type="button" class="canal {{ $canal === 'transport' ? 'on' : '' }}" wire:click="$set('canal', 'transport')">Places en véhicule</button>
                    <button type="button" class="canal {{ $canal === 'billet' ? 'on' : '' }}" wire:click="$set('canal', 'billet')">Billets d’événement</button>
                </div>

                <fieldset>
                    <legend>Événement</legend>
                    <div class="opts">
                        @foreach ($this->evenements as $evt)
                            <label class="opt" wire:key="evt-{{ $evt->id }}">
                                <input type="radio" name="evenement" wire:model.live="evenement_id" value="{{ $evt->id }}">
                                <span>
                                    <b>{{ $evt->titre }}</b>
                                    <small>{{ $evt->lieu }} · {{ $evt->debut_at->format('d/m/Y H:i') }}</small>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                @if ($canal === 'transport')
                    <fieldset>
                        <legend>Départ</legend>
                        <div class="opts">
                            @forelse ($this->departs as $d)
                                <label class="opt" wire:key="dep-{{ $d->id }}">
                                    <input type="radio" name="depart" wire:model.live="depart_id" value="{{ $d->id }}">
                                    <span>
                                        <b>{{ $d->depart_at->format('d/m à H:i') }}</b>
                                        <small>{{ $d->placesRestantes() }} place(s) restante(s){{ $d->vehicule ? ' · '.$d->vehicule->nom : '' }}</small>
                                    </span>
                                </label>
                            @empty
                                <p class="hint">Aucun départ ouvert pour cet événement.</p>
                            @endforelse
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend>Zone de ramassage</legend>
                        <div class="opts">
                            @forelse ($this->tarifs as $t)
                                <label class="opt" wire:key="z-{{ $t->id }}">
                                    <input type="radio" name="zone" wire:model.live="zone_id" value="{{ $t->zone_id }}">
                                    <span>
                                        <b>{{ $t->zone->nom }}</b>
                                        <small>{{ number_format($t->prix, 0, ',', ' ') }} FCFA / place
                                            @if ($t->heure_ramassage) · ramassage {{ \Illuminate\Support\Str::of($t->heure_ramassage)->substr(0, 5) }} @endif
                                        </small>
                                    </span>
                                </label>
                            @empty
                                <p class="hint">Aucune zone pour ce départ.</p>
                            @endforelse
                        </div>
                    </fieldset>
                @else
                    <fieldset>
                        <legend>Catégorie</legend>
                        <div class="opts">
                            @forelse ($this->typesBillets as $cat)
                                <label class="opt" wire:key="cat-{{ $cat->id }}">
                                    <input type="radio" name="categorie" wire:model.live="type_billet_id" value="{{ $cat->id }}">
                                    <span>
                                        <b>{{ $cat->nom }}</b>
                                        <small>{{ number_format($cat->prix, 0, ',', ' ') }} FCFA · {{ $cat->placesRestantes() }} restant(s)</small>
                                    </span>
                                </label>
                            @empty
                                <p class="hint">Aucune catégorie en vente.</p>
                            @endforelse
                        </div>
                    </fieldset>
                @endif

                <fieldset>
                    <legend>{{ $canal === 'transport' ? 'Nombre de places' : 'Nombre de billets' }}</legend>
                    <div class="stepper">
                        <button type="button" wire:click="decrement" aria-label="Retirer">−</button>
                        <output aria-live="polite" x-text="q">{{ $quantite }}</output>
                        <button type="button" wire:click="increment" aria-label="Ajouter">+</button>
                    </div>
                    <p class="hint">Maximum {{ $this->maxParCommande }} par commande · {{ $this->restant }} disponible(s).</p>
                </fieldset>

                <aside class="ticket recap" aria-label="Récapitulatif">
                    <div class="top">
                        <div class="tag">{{ $canal === 'transport' ? 'Transport' : 'Événement' }}</div>
                        <div class="zone">{{ $this->evenement?->titre ?? '—' }}</div>
                        <div class="who">
                            @if ($canal === 'transport')
                                {{ $this->tarif?->zone?->nom ?? 'Zone' }} · {{ $quantite }} place(s)
                            @else
                                {{ $this->typeBillet?->nom ?? 'Catégorie' }} · {{ $quantite }} billet(s)
                            @endif
                        </div>
                    </div>
                    <div class="perf"></div>
                    <div class="bot">
                        <div class="line"><span>Prix unitaire</span><span x-text="new Intl.NumberFormat('fr-FR').format(prix) + ' FCFA'">{{ number_format($this->prixUnitaire, 0, ',', ' ') }} FCFA</span></div>
                        <div class="total"><span>Total</span><span x-text="new Intl.NumberFormat('fr-FR').format(q * prix) + ' FCFA'">{{ number_format($this->prixUnitaire * $quantite, 0, ',', ' ') }} FCFA</span></div>
                    </div>
                </aside>

                <div class="bar">
                    @auth
                        <button class="cta" type="submit" @disabled(! $this->open || $this->prixUnitaire < 1) wire:loading.attr="disabled" wire:target="reserve">
                            <span wire:loading.remove wire:target="reserve">Réserver</span>
                            <span wire:loading wire:target="reserve">Enregistrement…</span>
                            <span class="amt" x-text="new Intl.NumberFormat('fr-FR').format(q * prix) + ' FCFA'">{{ number_format($this->prixUnitaire * $quantite, 0, ',', ' ') }} FCFA</span>
                        </button>
                    @else
                        <button class="cta" type="submit" @disabled(! $this->open || $this->prixUnitaire < 1)>
                            <span>Se connecter pour réserver</span>
                            <span class="amt" x-text="new Intl.NumberFormat('fr-FR').format(q * prix) + ' FCFA'">{{ number_format($this->prixUnitaire * $quantite, 0, ',', ' ') }} FCFA</span>
                        </button>
                    @endauth
                </div>
            </form>
        @endif
    @endif
</div>

@script
<script>
    async function compresserRecu(event, wire) {
        const fichier = event.target.files?.[0];
        if (!fichier) return;
        try {
            const img = await createImageBitmap(fichier);
            const max = 1280;
            const scale = Math.min(1, max / Math.max(img.width, img.height));
            const canvas = document.createElement('canvas');
            canvas.width = Math.round(img.width * scale);
            canvas.height = Math.round(img.height * scale);
            canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
            const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.82));
            const compact = new File([blob], 'recu.jpg', { type: 'image/jpeg' });
            await wire.upload('capture', compact, () => {}, () => {}, (p) => { wire.set('uploadProgress', p); });
            wire.set('uploadProgress', 100);
        } catch (e) {
            await wire.upload('capture', fichier);
        }
    }
    window.compresserRecu = compresserRecu;
</script>
@endscript
