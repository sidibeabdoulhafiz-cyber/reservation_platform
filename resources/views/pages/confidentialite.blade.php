<x-layouts.site title="Confidentialité et cookies" description="Comment CotaTrans protège vos données personnelles et utilise les cookies : réservation, paiement, billets, mesure d’audience.">
    <article class="medium legal">
        <p class="kicker">Vos données</p>
        <h1>Confidentialité et cookies</h1>
        <p class="lead-txt">CotaTrans collecte uniquement les informations nécessaires pour organiser ton transport et t’envoyer tes billets.</p>

        <h2>Données que nous utilisons</h2>
        <ul>
            <li><b>Compte :</b> nom, e-mail, téléphone, pour créer ton compte, t’envoyer tes billets et les rappels de départ.</li>
            <li><b>Réservation :</b> événement, arrêt, noms des passagers, pour éditer un billet QR par passager et préparer la liste de bord.</li>
            <li><b>Paiement :</b> capture du reçu, montant et ID de transaction, pour vérifier ton paiement. Nous ne stockons aucun code secret Mobile Money.</li>
            <li><b>Messages :</b> le contenu des formulaires « Organisateurs » et « Nous contacter », pour te répondre.</li>
        </ul>
        <p>Ces données ne sont jamais vendues. Elles sont partagées uniquement avec nos prestataires techniques (hébergement, envoi d’e-mails via Brevo), et avec l’agent de bord pour le contrôle des billets.</p>

        <h2 id="cookies">Cookies</h2>
        <p><b>Cookies indispensables (toujours actifs) :</b> ils maintiennent ta connexion, sécurisent les formulaires et conservent ta réservation en cours. Sans eux, le site ne peut pas fonctionner.</p>
        <p><b>Cookies de mesure d’audience (facultatifs) :</b> s’ils sont activés, Google Analytics et le pixel Meta nous aident à comprendre la fréquentation et à mesurer nos publicités. Ils ne sont déposés qu’avec ton accord, et tu peux changer d’avis à tout moment.</p>
        <p><button type="button" class="btn btn-ghost" onclick="window.dispatchEvent(new CustomEvent('ct-cookies'))">Gérer mes cookies</button></p>

        <h2>Tes droits</h2>
        <p>Tu peux consulter, corriger ou supprimer tes données depuis ton <a href="{{ route('profile.edit') }}">profil</a>, ou en nous écrivant via la page <a href="{{ route('contact') }}">Nous contacter</a>.</p>
    </article>
    <style>
        .legal h1 { color: var(--brand); }
        .legal h2 { margin-top: 30px; color: var(--fg); font-size: 1.3rem; }
        .legal ul { padding-left: 20px; display: grid; gap: 8px; }
        .legal p, .legal li { color: #334155; }
        .lead-txt { color: var(--muted); font-size: 1.08rem; }
    </style>
</x-layouts.site>
