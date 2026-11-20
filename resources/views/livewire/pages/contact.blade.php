<div class="page-form">
    <section class="form-intro">
        <p class="kicker">Contact</p>
        <h1>Une question ? Écris-nous.</h1>
        <p class="lead-txt">Réservation, paiement, horaires de départ, arrêt : l’équipe CotaTrans te répond rapidement.</p>
        <ul class="perks">
            <li><x-ico name="clock" /> <span><b>Réponse sous 24 h</b> ouvrées, souvent bien plus vite</span></li>
            <li><x-ico name="ticket" /> <span>Pour une réservation, indique ta <b>référence RES-…</b></span></li>
            @if (strlen($waNum = preg_replace('/\D/', '', (string) \App\Models\Setting::get('whatsapp_numero'))) >= 8)
                <li><x-ico name="chat" /> <span>Urgent le jour J ? <a href="https://wa.me/{{ $waNum }}" target="_blank" rel="noopener"><b>Écris-nous sur WhatsApp</b></a></span></li>
            @endif
        </ul>
        <p class="hint">Organisateur d’événement ? <a href="{{ route('organisateurs') }}">Utilise plutôt ce formulaire</a>.</p>
    </section>

    <section class="panel form-card">
        @if ($envoye)
            <div class="done" role="status">
                <span class="done-ico"><x-ico name="check-circle" size="34" /></span>
                <h2>Message envoyé !</h2>
                <p>Merci {{ \Illuminate\Support\Str::of($nom)->before(' ') }}, nous te répondons à <strong>{{ $email }}</strong>.</p>
                <a href="{{ route('home') }}" class="btn btn-ghost">Retour à l’accueil</a>
            </div>
        @else
            <h2>Ton message</h2>
            <form wire:submit="envoyer" novalidate>
                <div class="hp" aria-hidden="true"><label>Site web <input type="text" wire:model="site_web" tabindex="-1" autocomplete="off"></label></div>
                <div class="grid2">
                    <label class="field">Nom *
                        <input type="text" wire:model.blur="nom" autocomplete="name">
                        @error('nom') <span class="err">{{ $message }}</span> @enderror
                    </label>
                    <label class="field">E-mail *
                        <input type="email" wire:model.blur="email" autocomplete="email">
                        @error('email') <span class="err">{{ $message }}</span> @enderror
                    </label>
                </div>
                <label class="field">Téléphone
                    <input type="tel" wire:model.blur="tel" inputmode="tel" autocomplete="tel" placeholder="Facultatif">
                </label>
                <label class="field">Sujet *
                    <input type="text" wire:model.blur="sujet" placeholder="Ex. Question sur ma réservation RES-…">
                    @error('sujet') <span class="err">{{ $message }}</span> @enderror
                </label>
                <label class="field">Message *
                    <textarea rows="6" wire:model.blur="message" maxlength="3000"></textarea>
                    @error('message') <span class="err">{{ $message }}</span> @enderror
                </label>
                <button type="submit" class="cta center" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="envoyer">Envoyer</span>
                    <span wire:loading wire:target="envoyer">Envoi…</span>
                </button>
            </form>
        @endif
    </section>
    @include('livewire.pages.partials.form-styles')
</div>
