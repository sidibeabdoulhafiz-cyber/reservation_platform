# Mise en production de CotaTrans

Ce guide sert à **mettre le site en ligne toi-même** puis à **le tester avant de l'annoncer**.
Compte environ 1 h pour la première fois.

---

## 0. Ce qu'il faut chez l'hébergeur

| Besoin | Pourquoi |
|---|---|
| **PHP 8.4** (obligatoire) | Le projet utilise Laravel 13. Avec PHP 8.3 ou moins, le site ne démarre pas. |
| Extensions PHP : `pdo_mysql`, `mbstring`, `intl`, `gd`, `zip`, `fileinfo`, `openssl`, `dom` | Base de données, QR codes, images |
| **MySQL** (ou MariaDB) | Base de données |
| **Accès SSH** (terminal) | Pour lancer les commandes `php artisan …` |
| **Composer** | Installer les dépendances |
| **Certificat SSL (HTTPS)** | Obligatoire : paiement, connexion, cookies sécurisés |
| **Tâche cron** | Rappels J-1 / H-2 et expiration des réservations non payées |
| Dossier racine du site = **`public/`** | Le reste du projet (dont `.env`) ne doit jamais être accessible depuis le web |

> Pas besoin de Node.js ni de `npm run build` : le site n'utilise pas Vite.

---

## 1. Envoyer le code sur le serveur

En SSH, dans le dossier prévu (par ex. `~/cotatrans`) :

```bash
git clone https://github.com/sidibeabdoulhafiz-cyber/reservation_platform.git cotatrans
cd cotatrans
composer install --no-dev --optimize-autoloader
```

Fais pointer le domaine vers **`cotatrans/public`**.
Si ton hébergeur impose `public_html`, remplace `public_html` par un lien vers `public` :
`rm -rf ~/public_html && ln -s ~/cotatrans/public ~/public_html` (après avoir sauvegardé son contenu).

## 2. Le fichier `.env`

```bash
cp .env.production.example .env
nano .env            # remplis domaine, base MySQL, login/clé SMTP Brevo
php artisan key:generate
```

- `APP_DEBUG=false`, toujours, en production.
- Ne réutilise pas l'`APP_KEY` de ton PC.
- Ne colle jamais le contenu de `.env` dans une discussion, un e-mail ou une capture.

## 3. Base de données, fichiers et administrateur

```bash
php artisan migrate --force
php artisan storage:link
php artisan filament:assets
php artisan cotatrans:admin          # crée TON compte admin (mot de passe de 12 caractères ou plus)
php artisan cotatrans:admin --role=controleur   # un compte par agent de bord / chauffeur
```

Droits d'écriture : `chmod -R 775 storage bootstrap/cache`.

> **Ne lance pas `php artisan db:seed` en production.** Il crée des comptes de démonstration (mot de passe « password »). Le projet le bloque, mais ne le lance pas quand même.

## 4. Optimiser

```bash
php artisan optimize
```

Après chaque mise à jour du code :

```bash
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear && php artisan optimize
```

## 5. La tâche cron (une seule ligne)

Dans le panneau de l'hébergeur (Tâches cron), toutes les minutes :

```
* * * * * cd /chemin/vers/cotatrans && php artisan schedule:run >> /dev/null 2>&1
```

Elle libère les places non payées après le délai et envoie les rappels J-1 et H-2.

## 6. Contrôle automatique

```bash
php artisan cotatrans:verifier
```

Tout doit être en `[OK]`. Corrige chaque `[A FAIRE]` avant d'aller plus loin.

## 7. Réglages dans l'admin (`https://ton-domaine/admin`)

1. **Réglages → Paiement** : lien Wave Business, délai de paiement, « Réservations ouvertes ».
2. **Réglages → E-mails et contact** : e-mail de réception, téléphone, **numéro WhatsApp**. Clique sur **« Envoyer un e-mail de test »**.
3. **Réglages → Réseaux sociaux** : liens Facebook, Instagram, TikTok…
   **Réglages → E-mails envoyés aux clients** : coupez les e-mails facultatifs si vous approchez du quota gratuit Brevo (300 par jour). Comptez environ 5 à 6 e-mails par réservation avec tout activé.
4. **Zones**, **Véhicules**, puis ton premier **Événement** (affiche, catégorie, départs, arrêts, tarifs), puis **Publier**.

---

## 8. Recette : les tests à faire avant d'annoncer le site

Fais-les **sur le vrai site**, avec ton téléphone et un ordinateur. Coche au fur et à mesure.

### Visiteur
- [ ] La page d'accueil s'affiche en **https** (cadenas), sur mobile et sur ordinateur.
- [ ] Recherche « concert » : les résultats se filtrent. Les filtres **Concerts / Festivals / Matchs / Sorties** marchent.
- [ ] Page d'un événement : arrêts, prix, boutons **WhatsApp / Facebook** de partage.
- [ ] Le bouton WhatsApp vert (en bas à droite) ouvre une discussion avec le bon numéro.
- [ ] Formulaire **Organisateurs** puis **Nous contacter** : le message arrive dans l'admin et sur l'e-mail de réception, et l'expéditeur reçoit un accusé de réception.
- [ ] Une adresse inexistante (`/nimporte`) affiche la page « Page introuvable » CotaTrans.

### Inscription, connexion, sécurité
- [ ] Créer un compte avec une **vraie adresse** : un **code à 6 chiffres** arrive par e-mail (regarde les spams). Le saisir valide le compte.
- [ ] Se déconnecter, se reconnecter **sur le même appareil** (case « ne plus demander » cochée) : pas de nouveau code.
- [ ] Se connecter depuis **un autre téléphone** : un nouveau code est demandé.
- [ ] 5 mauvais codes de suite : blocage, il faut redemander un code.
- [ ] « Mot de passe oublié » : l'e-mail arrive et le lien fonctionne.
- [ ] `/admin` avec un compte client : accès refusé.

### Réservation et paiement (avec un vrai petit paiement Wave)
- [ ] Réserver 2 places sans être connecté : le bloc « Ton compte » attire l'attention, le compte se crée, le code est demandé, puis on revient sur la réservation.
- [ ] Payer **exactement** le montant avec Wave, puis envoyer la capture : le montant et l'ID sont lus et la preuve est acceptée.
- [ ] Essayer une capture d'un **autre montant** : elle est **refusée** (« Montant incorrect »).
- [ ] Renvoyer la **même capture** ou le **même ID** : refusé.
- [ ] Dans l'admin, **Paiements à vérifier → Valider** : le client reçoit ses billets par e-mail (1 QR par passager).
- [ ] Sur la page de suivi : **Imprimer / PDF**, **Ajouter à mon agenda**, **Itinéraire vers l'arrêt**.
- [ ] Une réservation non payée est libérée après le délai (cron actif).

### Jour J (agent de bord)
- [ ] Se connecter avec un compte **controleur** sur le téléphone de l'agent : il arrive directement sur le scanner.
- [ ] Scanner un billet à l'aller : vert. Le rescanner : « déjà scanné ». Scanner au retour : vert.
- [ ] Si la caméra ne s'ouvre pas, la saisie du numéro du billet fonctionne. (Le scan sans connexion n'est pas encore disponible : prévois des données mobiles pour l'agent.)

### Admin
- [ ] Le **Tableau de bord** affiche les tâches à faire (reçus, messages, brouillons, départs sans véhicule).
- [ ] **Messages → Répondre** : l'organisateur reçoit la réponse, l'historique est gardé.
- [ ] **Messages → Créer l'événement** : un brouillon est créé, on le complète puis on le publie.
- [ ] **Réglages → Bandeau d'annonce** : le bandeau apparaît en haut du site et peut être fermé.

---

## 9. Sécurité : ce qui est déjà en place

- Code par e-mail à l'inscription et à chaque nouvel appareil (admin compris). Changer de mot de passe révoque les appareils de confiance.
- Mots de passe : 8 caractères minimum avec lettres et chiffres. Comptes admin créés en ligne de commande avec 12 caractères minimum.
- Limitation des tentatives (connexion, code, formulaires, envois de preuves) et piège anti-robots sur les formulaires.
- Captures de paiement stockées hors du dossier public, visibles uniquement par le client concerné et l'admin.
- En-têtes de sécurité (anti-clickjacking, nosniff, HSTS en HTTPS) et pages personnelles jamais mises en cache.
- Comptes désactivés ou supprimés déconnectés immédiatement. Données de démonstration bloquées en production.

**À faire de ton côté :**
- Sauvegarde automatique quotidienne de la base MySQL (option de l'hébergeur).
- Mot de passe admin unique, non partagé : un compte par personne.
- Tenir PHP et les dépendances à jour (`composer update` de temps en temps, puis refaire les tests).
