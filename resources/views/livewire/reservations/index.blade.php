<div>
    <header class="hero">
        <h1>Mes réservations</h1>
    </header>

    @if (session('success'))
        <div class="alert alert-ok">{{ session('success') }}</div>
    @endif

    @forelse ($reservations as $r)
        <article class="rcard" wire:key="res-{{ $r->id }}">
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
                    <p>Payez <b>{{ number_format($r->montant, 0, ',', ' ') }} FCFA</b> sur Wave, puis envoyez la capture du reçu.</p>
                    <div class="actions">
                        @if ($this->waveLink)
                            <a class="btn" href="{{ $this->waveLink }}" target="_blank" rel="noopener">Payer sur Wave</a>
                        @endif
                        <button type="button" class="btn btn-ghost" wire:click="openProof({{ $r->id }})">J'ai payé : envoyer le reçu</button>
                    </div>

                    @if ($proofFor === $r->id)
                        <form class="proof" wire:submit="submitProof">
                            <label>
                                Identifiant de transaction Wave
                                <input type="text" wire:model="paiement_id" placeholder="T_XXXXXXXX" autocomplete="off">
                            </label>
                            @error('paiement_id') <span class="err">{{ $message }}</span> @enderror

                            <label>
                                Capture d'écran du reçu
                                <input type="file" wire:model="capture" accept="image/*">
                            </label>
                            <div class="hint" wire:loading wire:target="capture">Téléversement en cours…</div>
                            @error('capture') <span class="err">{{ $message }}</span> @enderror

                            <div class="actions">
                                <button class="btn" type="submit" wire:loading.attr="disabled" wire:target="submitProof,capture">Envoyer le reçu</button>
                                <button class="btn btn-ghost" type="button" wire:click="closeProof">Annuler</button>
                            </div>
                        </form>
                    @endif
                </div>
            @elseif ($r->statut->value === 'en_verification')
                <p class="note">Reçu envoyé (transaction {{ $r->paiement_id }}). La vérification est en cours.</p>
            @elseif ($r->statut->value === 'payee')
                <p class="note note-ok">Paiement confirmé · transaction {{ $r->paiement_id }}</p>
            @endif
        </article>
    @empty
        <div class="panel empty">
            <p>Vous n'avez encore aucune réservation.</p>
            <a class="btn" href="{{ route('home') }}">Réserver une place</a>
        </div>
    @endforelse

    {{ $reservations->links('livewire::simple-bootstrap') }}
</div>
