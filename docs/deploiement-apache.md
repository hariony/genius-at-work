# Déploiement sur un serveur Apache + PHP (sans Docker)

Ce guide couvre l'hébergement actuel de genius-at-work.com : un serveur administré par un
tiers, avec Apache, PHP 8.4 et MariaDB 11.8, joignable en SSH par clé. Le déploiement Docker
sur un VPS reste décrit dans `deploiement.md`.

## Répartition

```
~/public_html/        genius-at-work.com (et www) : le site compilé (pages statiques)
~/gaw-api/            l'API Symfony et le back-office
  .env.local          secrets (droits 600), créé à la main, jamais envoyé par le script
  public/             racine web de api.genius-at-work.com et office.genius-at-work.com
  public/media/       captures envoyées depuis le back-office (jamais écrasées)
  var/log/            journaux (un fichier par jour, sept gardés, avertissements et erreurs)
~/backups/            trois dernières sauvegardes de la base (droits 600)
~/.my.cnf             accès de mariadb-dump à la base (droits 600, fourni par l'hébergeur)
Base MariaDB          genius_at_work_gaw (l'ancienne base WordPress n'est pas touchée)
Worker                service viraka-gaw-messenger, installé par l'hébergeur : e-mails du
                      formulaire, purge RGPD nocturne ; journal ~/logs/gaw-messenger.log
```

Chaque sauvegarde est aussi copiée **sur le poste qui déploie** (`backups/`, non versionné).
Quota : 2 Go pour tout le compte (fichiers, mails, sauvegardes, journaux).

## Ce que fait `infra/apache/deploy.sh`

| Commande                      | Effet                                                                                                                                                                                                                           |
| ----------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `infra/apache/deploy.sh api`  | Vérifie les dépendances (`composer audit`), sauvegarde la base (serveur et poste, vérifiée), envoie le code, puis sur le serveur : `composer install`, migrations, cache, `messenger:stop-workers` ; contrôle `/api/v1/health`. |
| `infra/apache/deploy.sh site` | Compile le site avec le contenu de l'API en ligne, montre ce qui changerait, publie, vérifie la page d'accueil.                                                                                                                 |
| `infra/apache/deploy.sh all`  | Les deux, dans cet ordre.                                                                                                                                                                                                       |
| `--dry-run`                   | Montre les fichiers qui changeraient, sans rien modifier.                                                                                                                                                                       |
| `--yes`                       | Ne pose pas de question.                                                                                                                                                                                                        |
| `--replace-wordpress`         | Une seule fois, au lancement : autorise le remplacement de l'ancien WordPress (refusé sans cette option, même avec `--yes`).                                                                                                    |

Le script déploie l'état du poste (fichiers suivis par Git et nouveaux fichiers non ignorés) et
prévient s'il diffère du dernier commit. Il ne touche jamais à `.well-known/` (certificat), au
fichier de validation Google Search Console, ni aux dossiers `stats/` (statistiques de visites),
`phpmyadmin/` et `roundcube/` de l'hébergeur. Il pose les droits demandés : 755 pour les dossiers, 644 pour les fichiers.

L'ordre des opérations sur le serveur est celui imposé par l'hébergeur. Les migrations ne font
qu'ajouter (colonnes, tables, textes manquants) : l'ancienne version de l'API fonctionne sur le
nouveau schéma. En cas d'échec, redéployer le commit précédent ; la sauvegarde n'est restaurée
que sur décision explicite. Aucune commande ne supprime de base (la CI le vérifie :
`infra/scripts/check-safe-ops.sh`).

## Mise en place (une seule fois)

### 1. Préparé par l'administrateur du serveur

Base dédiée, sous-domaines, worker, expéditeur, réglages PHP : voir
[Cadre fixé par l'hébergeur](#cadre-fixé-par-lhébergeur).

### 2. Sur le poste qui déploie

- Accès SSH : bloc `Host genius` dans `~/.ssh/config` (fiche de l'administrateur), testé avec
  `ssh genius`.
- `cp infra/apache/deploy.env.example infra/apache/deploy.env`, puis renseigner la clé de site
  Turnstile de production.
- Docker lancé et image `gaw-api:dev` construite (`docker compose build api`) : elle prépare
  l'API avec le même PHP que le serveur. Node 24 et pnpm pour le site.

### 3. Sauvegarder l'ancien site

Avant tout, sur ce poste (commandes de la fiche de l'administrateur) :

```sh
mkdir -p ancien_site
rsync -avz genius:public_html/ ancien_site/public_html/
ssh genius "cd public_html && wp db export - | gzip" > ancien_site/genius_at_work_$(date +%Y%m%d).sql.gz
```

### 4. Secrets de l'API

Sur le serveur, créer `~/gaw-api/.env.local` à partir de `infra/apache/env.local.example`
(`DATABASE_URL` fourni par l'hébergeur, en `127.0.0.1:3306`), puis
`chmod 600 ~/gaw-api/.env.local`. Le script refuse de déployer sans `APP_ENV=prod`,
`APP_DEBUG=0`, `APP_SECRET`, une vraie clé Turnstile et un `MAILER_DSN` actif.

### 5. Premier déploiement de l'API

```sh
infra/apache/deploy.sh api
ssh genius 'cd gaw-api && php bin/console app:content:import'   # textes, projets, images
ssh -t genius 'cd gaw-api && php bin/console app:admin:create contact@genius-at-work.com'
```

La création du compte demande le mot de passe : la taper soi-même, dans son terminal. À la
première connexion sur `office.`, le back-office impose l'activation de la double
authentification (QR code à scanner avec une application d'authentification). Un compte par
personne, jamais de compte partagé.

Vérifier : `https://api.genius-at-work.com/api/v1/health` répond `{"status":"ok"}`, et
`https://office.genius-at-work.com/` mène à l'écran de connexion.

### 6. Lancement du site

```sh
infra/apache/deploy.sh site --dry-run                # relire : WordPress sera remplacé
infra/apache/deploy.sh site --replace-wordpress
```

Puis, comme le demande l'administrateur : tester `https://genius-at-work.com` et `www`, un envoi
du formulaire (les deux e-mails doivent arriver), et lire `~/logs/error_log` et `~/logs/php_log`.

## Le site sous Apache (`apps/web/public/.htaccess`)

- HTTPS et domaine sans `www` imposés, sauf pour `/.well-known/` (renouvellement du certificat).
- Pages 404 en français et en anglais (`/en/…`).
- Redirections 301 de l'ancien WordPress, d'après le plan de `docs/content-audit.md` §13 et les
  adresses réellement recensées : réalisations (deux slugs corrigés), catégories (liste filtrée),
  anciennes pages de services (page du métier), technologies, équipe, conditions. Les pages
  WordPress sans équivalent (article d'exemple, auteur, flux, `wp-admin`, `xmlrpc.php`…)
  répondent 410 (retirées définitivement).
- En-têtes de sécurité (ceux de `_headers` sur Cloudflare) ; cache permanent pour `/_astro/`,
  un jour pour les images de partage, revalidation pour les pages.

Sur `api.` et `office.`, l'hébergeur ignore les `.htaccess` : le serveur envoie tout vers
`public/index.php`, seul fichier PHP exécuté. Le `.htaccess` de `apps/api/public/` ne sert
qu'ailleurs.

## Règles de l'hébergeur

- E-mails : `MAILER_DSN=sendmail://default` (le port 25 est fermé aux comptes). Expéditeur
  `contact@genius-at-work.com` uniquement ; l'adresse du visiteur seulement en `Reply-To`.
  L'accusé de réception ne contient aucun texte saisi par le visiteur.
- Aucun `crontab` ni processus permanent autre que le worker installé par l'hébergeur.
- Aucun autre point d'entrée PHP sous `public/` (pas d'Adminer, de `phpinfo`…).
- Droits : 644 pour les fichiers, 755 pour les dossiers, 600 pour `.env.local`, `.my.cnf` et
  les sauvegardes.
- Si SSH annonce un changement de clé du serveur, ne pas accepter : prévenir l'hébergeur.

## Opérations courantes

**Toute mise en production** (site, API ou back-office) est annoncée à l'hébergeur **la veille**,
avec la date et l'heure : il fait sa propre sauvegarde et reste disponible. Sans réponse de sa
part, on attend. Le script le rappelle et demande confirmation, même avec `--yes`.

| Situation                                   | Commande                      |
| ------------------------------------------- | ----------------------------- |
| Texte ou projet modifié dans le back-office | `infra/apache/deploy.sh site` |
| Code modifié (API, site ou les deux)        | `infra/apache/deploy.sh all`  |
| Voir ce qu'un déploiement changerait        | `… --dry-run`                 |

**Restaurer une sauvegarde** (décision explicite, jamais automatique) : la sauvegarde est un
fichier SQL compressé, dans `backups/` sur le poste. La restauration se fait par le tunnel SSH
décrit par l'administrateur, dans une base vide, après accord.

**Journaux** : `ssh genius 'tail -50 ~/logs/php_log ~/logs/error_log'`.

## Vérifié avant mise en ligne

Sur un serveur de test reproduisant celui-ci (Apache, PHP 8.4.26, client MariaDB 11.8, SSH par
clé, PHP exécuté sous le compte) : sauvegarde vérifiée, 16 migrations sur base vide, import du
contenu, back-office, CORS du site vers l'API, worker, refus de WordPress sans
`--replace-wordpress`, conservation de `.well-known/` et du fichier Google, droits 644/755,
toutes les redirections et les 404.

## Cadre fixé par l'hébergeur

Réponse de l'administrateur du serveur du 21/10/2026 (document transmis hors dépôt) : base,
sous-domaines, PHP, worker et expéditeur en place ; règles reprises dans
[Règles de l'hébergeur](#règles-de-lhébergeur). Toute exception se demande avant d'être codée.
Avant la mise en ligne de `office.`, la double authentification est obligatoire pour tous les
comptes du back-office. Toute mise en production est annoncée la veille (voir
[Opérations courantes](#opérations-courantes)).

La boîte contact@genius-at-work.com est personnelle (relevée en IMAP par son titulaire) : pas de
webmail (`/roundcube/` retiré), ni d'accès partagé, ni de redirection. Pour vérifier qu'un envoi
du formulaire est parti, demander à l'hébergeur de contrôler ses journaux. Pour tester avec sa
propre adresse, changer `CONTACT_TEAM_EMAIL` en local uniquement, jamais en production.
