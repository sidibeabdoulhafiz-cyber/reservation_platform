<div id="mes-reservations" class="mine" @if ($reservations->contains(fn ($r) => $r->statut->value === 'en_verification')) wire:poll.15s @endif>
    @if ($reservations->isNotEmpty())
        <h2>Mes réservations</h2>

        @if ($notice)
            <div class="alert alert-ok" role="status">{{ $notice }}</div>
        @endif

        @foreach ($reservations as $r)
            <article class="rcard {{ $highlight === $r->id ? 'new' : '' }}" wire:key="res-{{ $r->id }}">
                <div class="rhead">
                    <div>
                        <div class="mono">{{ $r->ref }}</div>
                        <div class="hint">{{ $r->created_at->format('d/m/Y à H:i') }}</div>
                    </div>
                    <span class="badge {{ $r->statut->value }}">{{ $r->statut->getLabel() }}</span>
                </div>

                <div class="rbody">
                    <div><span class="k">Zone</span>{{ $r->zone->nom }}</div>
                    <div><span class="k">Places</span>{{ $r->places }}</div>
                    <div><span class="k">Total</span><span class="mono">{{ number_format($r->montant, 0, ',', ' ') }} FCFA</span></div>
                </div>

                @if ($r->statut->value === 'en_attente')
                    <div class="steps">
                        @if ($r->motif_refus)
                            <div class="alert alert-err">Reçu refusé : {{ $r->motif_refus }}. Renvoyez un reçu valide.</div>
                        @endif
                        @if ($proofFor === $r->id)
                            <p>1. Payez <b>{{ number_format($r->montant, 0, ',', ' ') }} FCFA</b> sur Wave. 2. Revenez ici et envoyez la capture.</p>
                            <div class="actions">
                                @if ($this->waveLink)
                                    <a class="btn" href="{{ $this->waveLink }}" target="_blank" rel="noopener">Payer sur Wave</a>
                                @endif
                            </div>

                            <form class="proof" wire:submit="submitProof">
                                <label class="drop">
                                    <input type="file" wire:model="capture" accept="image/*">
                                    @if ($capture && $capture->isPreviewable())
                                        <img class="thumb" src="{{ $capture->temporaryUrl() }}" alt="Aperçu du reçu">
                                    @else
                                        <span wire:loading.remove wire:target="capture">Choisir la capture du reçu</span>
                                    @endif
                                    <span wire:loading wire:target="capture">Téléversement…</span>
                                </label>
                                @error('capture') <span class="err">{{ $message }}</span> @enderror

                                <label>
                                    Identifiant de transaction Wave
                                    <input type="text" wire:model="paiement_id" placeholder="T_XXXXXXXX" autocomplete="off" autocapitalize="characters" spellcheck="false">
                                </label>
                                @error('paiement_id') <span class="err">{{ $message }}</span> @enderror

                                <button class="btn" type="submit" wire:loading.attr="disabled" wire:target="submitProof,capture">
                                    <span wire:loading.remove wire:target="submitProof">Envoyer le reçu</span>
                                    <span wire:loading wire:target="submitProof">Envoi…</span>
                                </button>
                            </form>
                        @else
                            <div class="actions">
                                <button type="button" class="btn btn-ghost" wire:click="openProof({{ $r->id }})">Payer / envoyer le reçu</button>
                            </div>
                        @endif
                    </div>
                @elseif ($r->statut->value === 'en_verification')
                    <p class="note">Reçu envoyé (transaction {{ $r->paiement_id }}). Cette page se met à jour dès la validation.</p>
                @elseif ($r->statut->value === 'payee')
                    <p class="note note-ok">Paiement confirmé · transaction {{ $r->paiement_id }}</p>
                @endif
            </article>
        @endforeach
    @endif
</div>
