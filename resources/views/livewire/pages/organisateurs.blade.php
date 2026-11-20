<div class="page-form">
    <section class="form-intro">
        <p class="kicker">Organisateurs</p>
        <h1>Confiez-nous le transport de votre événement</h1>
        <p class="lead-txt">Concert, festival, match, journée rétro ou sortie : CotaTrans organise l’aller-retour de votre public, de la vente des places jusqu’au contrôle à la montée.</p>
        <ul class="perks" id="avantages-orga">
            <li><x-ico name="route" /> <span><b>Lignes et arrêts sur mesure</b> dans les quartiers de votre public</span></li>
            <li><x-ico name="wallet" /> <span><b>Vente en ligne</b> et paiement Mobile Money, sans gestion de caisse</span></li>
            <li><x-ico name="qr" /> <span><b>Billets QR</b> contrôlés à l’aller et au retour</span></li>
            <li><x-ico name="chat" /> <span><b>Un lien à partager</b> à votre communauté dès l’accord trouvé</span></li>
        </ul>
        <p class="hint">Réponse sous 48 h ouvrées. Aucun engagement.</p>
    </section>

    <section class="panel form-card">
        @if ($envoye)
            <div class="done" role="status">
                <span class="done-ico"><x-ico name="check-circle" size="34" /></span>
                <h2>Demande envoyée !</h2>
                <p>Merci {{ \Illuminate\Support\Str::of($nom)->before(' ') }}. Nous étudions votre événement et revenons vers vous à <strong>{{ $email }}</strong>.</p>
                <a href="{{ route('home') }}" class="btn btn-ghost">Retour à l’accueil</a>
            </div>
        @else
            <h2>Votre événement en 1 minute</h2>
            <form wire:submit="envoyer" novalidate>
                <div class="hp" aria-hidden="true"><label>Site web <input type="text" wire:model="site_web" tabindex="-1" autocomplete="off"></label></div>

                <fieldset>
                    <legend>Vous</legend>
                    <div class="grid2">
                        <label class="field">Nom complet *
                            <input type="text" wire:model.blur="nom" autocomplete="name">
                            @error('nom') <span class="err">{{ $message }}</span> @enderror
                        </label>
                        <label class="field">Organisation
                            <input type="text" wire:model.blur="organisation" autocomplete="organization" placeholder="Facultatif">
                        </label>
                        <label class="field">E-mail *
                            <input type="email" wire:model.blur="email" autocomplete="email">
                            @error('email') <span class="err">{{ $message }}</span> @enderror
                        </label>
                        <label class="field">Téléphone / WhatsApp *
                            <input type="tel" wire:model.blur="tel" inputmode="tel" autocomplete="tel">
                            @error('tel') <span class="err">{{ $message }}</span> @enderror
                        </label>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>L’événement</legend>
                    <label class="field">Nom de l’événement *
                        <input type="text" wire:model.blur="evenement" placeholder="Ex. Festival des grillades">
                        @error('evenement') <span class="err">{{ $message }}</span> @enderror
                    </label>
                    <div class="grid2">
                        <label class="field">Date
                            <input type="date" wire:model.blur="date_evenement" min="{{ now()->toDateString() }}">
                            @error('date_evenement') <span class="err">{{ $message }}</span> @enderror
                        </label>
                        <label class="field">Participants attendus
                            <input type="number" inputmode="numeric" min="1" wire:model.blur="participants" placeholder="Ex. 2000">
                            @error('participants') <span class="err">{{ $message }}</span> @enderror
                        </label>
                    </div>
                    <label class="field">Lieu
                        <input type="text" wire:model.blur="lieu" placeholder="Ex. Stade FHB, Abidjan">
                    </label>
                    <label class="field">Précisions (quartiers de votre public, horaires, besoins…)
                        <textarea rows="4" wire:model.blur="message" maxlength="3000"></textarea>
                        @error('message') <span class="err">{{ $message }}</span> @enderror
                    </label>
                </fieldset>

                <button type="submit" class="cta center" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="envoyer">Envoyer ma demande</span>
                    <span wire:loading wire:target="envoyer">Envoi…</span>
                </button>
                <p class="hint" style="text-align:center">En envoyant ce formulaire, vous acceptez d’être recontacté par CotaTrans. <a href="{{ route('confidentialite') }}">Confidentialité</a></p>
            </form>
        @endif
    </section>
    @include('livewire.pages.partials.form-styles')
</div>
