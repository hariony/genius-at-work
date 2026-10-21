# Déploiement et exploitation

Ce guide couvre la mise en place de la production sur un VPS avec Docker et les opérations
courantes. Hébergement actuel (Apache, PHP 8.4, MariaDB, sans Docker) : `deploiement-apache.md`.
Rien n'est déployé tant que la variable de dépôt `DEPLOY_ENABLED` ne vaut pas `true`.

## Vue d'ensemble

| Élément       | Où                                | Comment                                                               |
| ------------- | --------------------------------- | --------------------------------------------------------------------- |
| Site (Astro)  | Cloudflare Workers Static Assets  | `wrangler deploy` depuis GitHub Actions                               |
| API (Symfony) | VPS dans l'UE, Docker Compose     | image GHCR, SSH à travers Cloudflare Tunnel, `infra/deploy/deploy.sh` |
| MySQL         | même VPS, volume Docker nommé     | jamais exposé sur Internet                                            |
| Accès entrant | Cloudflare Tunnel (`cloudflared`) | aucun port ouvert sur le VPS                                          |

Pipeline sur `main` : CI complète → site en préproduction (automatique) → site et API en
production (approbation manuelle dans l'environnement GitHub `production`).

## Base de données

L'API fonctionne sur **MySQL 8.4** (Docker, ce guide) et sur **MariaDB 11.8** (hébergement
mutualisé). La CI applique les migrations et lance toute la suite de tests sur les deux. Avec
MariaDB, `DATABASE_URL` doit le dire : `…?serverVersion=11.8.6-MariaDB&charset=utf8mb4`.

Dans une migration, du JSON se passe par `JSON_EXTRACT(:valeur, '$')`, jamais par
`CAST(:valeur AS JSON)`, que MariaDB ne connaît pas. Pour lancer les tests sur une vraie base
plutôt que sur SQLite : `TEST_DATABASE_URL=… bin/phpunit`, avec une base dédiée aux tests, car
elle est vidée à chaque test.

## Règles de sécurité du pipeline

- Aucune commande destructive : pas de suppression de base, de schéma ni de volume. La CI le
  vérifie (`infra/scripts/check-safe-ops.sh`) et échoue si une telle commande apparaît.
- La base est sauvegardée avant chaque migration (`/srv/gaw/backups/`), et la sauvegarde est
  vérifiée avant de continuer.
- Les migrations sont lancées avec la nouvelle image avant sa mise en service : si elles
  échouent, l'ancienne version continue de tourner.
- Si l'API n'est pas saine après redémarrage, l'image précédente est remise en service
  automatiquement. Les migrations ne sont pas annulées : elles doivent rester compatibles avec
  la version précédente (ajouter d'abord, supprimer dans un déploiement ultérieur).
- Aucun secret dans Git : secrets GitHub pour la CI, fichier `/srv/gaw/.env` (droits 600) sur
  le serveur. La CI cherche les secrets dans tout l'historique (gitleaks).
- L'image de production refuse de démarrer avec une clé Turnstile de test, un `APP_SECRET`
  vide ou un transport e-mail inactif.

## Mise en place (une seule fois)

### 1. Serveur

1. VPS Debian ou Ubuntu dans l'UE, avec Docker Engine et le plugin Compose.
2. Un utilisateur `deploy` membre du groupe `docker`, connexion par clé uniquement.
3. Dossier `/srv/gaw` appartenant à `deploy`, contenant `.env` rempli à partir de
   `infra/docker/prod.env.example` (`chmod 600 .env`).
4. Pare-feu : tout le trafic entrant fermé (SSH compris), le tunnel sort vers Cloudflare.

### 2. Cloudflare

1. **Tunnel** (Zero Trust › Networks › Tunnels) : créer un tunnel, copier son jeton dans
   `CLOUDFLARE_TUNNEL_TOKEN` du `.env` serveur. Routes publiques :
   - `api.genius-at-work.com` → `http://api:8080`
   - `office.genius-at-work.com` → `http://api:8080` (back-office : `ADMIN_HOST` dans le `.env`)
   - `ssh.genius-at-work.com` → `ssh://host.docker.internal:22` (ou l'IP de la passerelle Docker)
2. **Access** : une application sur `ssh.genius-at-work.com` avec un _service token_ pour la
   CI ; une application sur le domaine de préproduction pour l'équipe ; une application sur
   `office.genius-at-work.com` (e-mails de l'équipe) : le back-office n'est joignable qu'après
   une connexion Cloudflare, avant même son propre écran de connexion.
3. **Turnstile** : un widget pour `genius-at-work.com` (clé de site publique côté site, clé
   secrète dans le `.env` serveur).
4. **Jeton d'API** pour la CI : modèle « Edit Cloudflare Workers ».

### 3. GitHub

Variable de dépôt : `DEPLOY_ENABLED=true` (à activer en dernier).

Environnements `staging` et `production` (règle « Required reviewers » sur `production`) :

| Nom                               | Type     | Environnement       | Exemple                                |
| --------------------------------- | -------- | ------------------- | -------------------------------------- |
| `PUBLIC_SITE_URL`                 | variable | staging, production | `https://genius-at-work.com`           |
| `PUBLIC_API_URL`                  | variable | staging, production | `https://api.genius-at-work.com`       |
| `PUBLIC_TURNSTILE_SITE_KEY`       | variable | staging, production | clé de site publique                   |
| `CLOUDFLARE_ACCOUNT_ID`           | variable | staging, production |                                        |
| `CLOUDFLARE_API_TOKEN`            | secret   | staging, production |                                        |
| `DEPLOY_SSH_HOST`                 | variable | production          | `ssh.genius-at-work.com`               |
| `DEPLOY_SSH_USER`                 | variable | production          | `deploy`                               |
| `DEPLOY_SSH_KEY`                  | secret   | production          | clé privée dédiée à la CI              |
| `DEPLOY_SSH_KNOWN_HOSTS`          | secret   | production          | sortie de `ssh-keyscan` sur le serveur |
| `CF_ACCESS_CLIENT_ID` / `_SECRET` | secret   | production          | service token Cloudflare Access        |

La préproduction n'a pas d'API dédiée : son formulaire de contact ne fonctionne que si
`PUBLIC_API_URL` pointe vers une API qui autorise son origine (`CORS_ALLOW_ORIGIN`).

### 4. Bascule du domaine

Le site est d'abord publié sur l'adresse `*.workers.dev`. Au lancement, ajouter dans
`infra/cloudflare/wrangler.jsonc` les routes `genius-at-work.com` et `www.genius-at-work.com`
(`custom_domain: true`), en même temps que les redirections 301 de l'ancien site.

## Opérations courantes

**Déployer** : fusionner sur `main`, puis approuver l'environnement `production` dans
l'onglet Actions. Relancer un déploiement : _Run workflow_ sur « Déploiement ».

**Version en service** : `cat /srv/gaw/.current-image` ; historique dans `deploy-history.log`.

**Revenir à une version précédente** : `./deploy.sh <image précédente>` depuis `/srv/gaw`.

**Restaurer une sauvegarde** (opération manuelle, à décider explicitement) :

```sh
cd /srv/gaw
gzip -dc backups/pre-deploy-AAAAMMJJTHHMMSSZ.sql.gz \
  | docker compose -f compose.prod.yaml exec -T mysql \
    sh -c 'exec mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"'
```

**Messages en échec** (e-mails non envoyés après 3 tentatives) :

```sh
docker compose -f compose.prod.yaml exec api php bin/console messenger:failed:show
docker compose -f compose.prod.yaml exec api php bin/console messenger:failed:retry
```

**Journaux** : `docker compose -f compose.prod.yaml logs -f api worker scheduler`.

Les sauvegardes s'accumulent dans `/srv/gaw/backups/` : aucune n'est supprimée
automatiquement. Une copie hors du serveur et une rotation seront mises en place à l'étape
sécurité.
