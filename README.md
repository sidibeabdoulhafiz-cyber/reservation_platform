# CotaTrans

Plateforme de réservation de transport aller-retour pour les événements (concerts, festivals, matchs…).
Laravel 13 · Livewire 4 · Filament 5 (console admin).

## Installation locale

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan storage:link        # indispensable pour afficher les affiches des événements
php artisan serve
```

Comptes de démonstration (mot de passe `password`) :
`admin@cotatrans.test` · `client@cotatrans.test` · `controle@cotatrans.test`

Planificateur (expiration des réservations non payées) : `php artisan schedule:work` en local, ou une tâche cron `* * * * * php artisan schedule:run` en production.

## Parcours passager

1. **Accueil** (`/`) : cartes des événements avec affiche, date, lieu et prix « à partir de ».
2. **Page événement** (`/evenements/{slug}`) : lien à partager par le promoteur. Le passager choisit le départ, son **arrêt sur l’itinéraire** (heure de passage, point de RDV), le nombre de places, et crée son compte **sur la même page**.
3. **Suivi** (`/reservations/{ref}`) : paiement Wave, envoi de la capture du reçu (avec aperçu et compression), compte à rebours du délai de paiement.
4. **Billets** : un QR code par place, page imprimable / PDF, lien facultatif vers le groupe WhatsApp de l’événement.
5. **Mes billets** (`/mes-billets`) : réservations à finaliser, à venir, historique.

## Console admin (`/admin`)

- **Événements** : affiche (recadrage 16:9 intégré), accroche, lien du groupe WhatsApp, bouton « Voir la page ».
- **Départs** : itinéraire avec arrêts **ordonnés par glisser-déposer**, point principal, heure de passage estimée, prix par arrêt.
- **Paiements** : validation ou refus des reçus. Le passager est prévenu par e-mail.
- **Réglages** : lien Wave, délai de paiement, bouton **« Envoyer un e-mail de test »**.

## E-mails (Brevo)

E-mails envoyés automatiquement : réservation créée (à payer), reçu bien reçu, billets confirmés (QR codes en pièces jointes) et reçu refusé (avec le motif).

Configuration dans `.env` :

```dotenv
MAIL_MAILER=brevo
BREVO_API_KEY=xkeysib-...           # https://app.brevo.com/settings/keys/api
MAIL_FROM_ADDRESS="billets@votre-domaine.ci"   # expéditeur validé dans Brevo
MAIL_FROM_NAME="CotaTrans"
```

Le connecteur Brevo (`app/Mail/Transport/BrevoTransport.php`) appelle directement l’API transactionnelle,
sans dépendance supplémentaire. Alternative : le relais SMTP Brevo (voir `.env.example`).

Pour envoyer les e-mails en arrière-plan : `MAIL_EN_FILE=true` puis `php artisan queue:work`.

## Tests

```bash
php artisan test
```
