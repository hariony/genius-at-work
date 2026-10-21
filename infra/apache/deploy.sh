#!/usr/bin/env bash
# shellcheck disable=SC1112 # apostrophes typographiques françaises dans les messages.
# Déploiement sur un serveur Apache + PHP 8.4 + MariaDB, sans Docker (docs/deploiement-apache.md).
# Lancé depuis un poste de l'équipe, à la racine du dépôt :
#
#   infra/apache/deploy.sh api    # API et back-office : sauvegarde, migrations, mise en service
#   infra/apache/deploy.sh site   # site statique, compilé avec le contenu de l'API en ligne
#   infra/apache/deploy.sh all    # les deux, dans cet ordre
#
# Options : --dry-run (montre ce qui changerait, sans rien modifier), --yes (pas de question),
# --replace-wordpress (une seule fois : remplacer l'ancien WordPress, après l'avoir sauvegardé).
# Réglages : infra/apache/deploy.env (modèle : deploy.env.example).
#
# Règles : aucune suppression de base ; la base est sauvegardée (sur le serveur et sur ce poste)
# et la sauvegarde vérifiée avant tout envoi ; ensuite, dans l'ordre imposé par l'hébergeur :
# code, dépendances, migrations, cache, arrêt du worker (il repart seul sur le nouveau code).
set -euo pipefail

cd "$(dirname "$0")/../.."
ROOT="$PWD"

log() { printf '\033[1m→ %s\033[0m\n' "$*"; }
fail() { printf '\033[31m✗ %s\033[0m\n' "$*" >&2; exit 1; }

TARGET="${1:-}"
shift || true
DRY_RUN=false
ASSUME_YES=false
REPLACE_WORDPRESS=false
for arg in "$@"; do
  case "$arg" in
    --dry-run) DRY_RUN=true ;;
    --yes) ASSUME_YES=true ;;
    --replace-wordpress) REPLACE_WORDPRESS=true ;;
    *) fail "Option inconnue : $arg" ;;
  esac
done
case "$TARGET" in api | site | all) ;; *) fail 'Usage : deploy.sh api|site|all [--dry-run] [--yes]' ;; esac

[ -f infra/apache/deploy.env ] || fail 'infra/apache/deploy.env absent (modèle : deploy.env.example).'
# shellcheck source=/dev/null
source infra/apache/deploy.env
: "${SSH_HOST:?}" "${API_DIR:?}" "${SITE_DIR:?}" "${API_URL:?}" "${SITE_URL:?}"
# Vide : Turnstile désactivé (la clé secrète de .env.local doit alors être vide aussi).
TURNSTILE_SITE_KEY="${TURNSTILE_SITE_KEY-}"
BACKUP_DIR="${BACKUP_DIR:-$ROOT/backups}"
# Options ssh supplémentaires (ex. « -F ~/.ssh/autre_config »), aussi utilisées par rsync.
read -r -a SSH_ARGS <<<"${SSH_OPTS:-}"
export RSYNC_RSH="ssh ${SSH_OPTS:-}"

confirm() {
  $ASSUME_YES && return 0
  read -r -p "$1 [o/N] " answer
  [[ "$answer" =~ ^[oOyY]$ ]] || fail 'Annulé.'
}

# Les commandes sont composées ici, variables comprises (SC2029 voulu).
# shellcheck disable=SC2029
remote() { ssh ${SSH_ARGS[@]+"${SSH_ARGS[@]}"} "$SSH_HOST" "$@"; }

# Droits demandés par l'hébergeur : 755 pour les dossiers, 644 pour les fichiers (fait sur le
# serveur : le rsync de macOS ne sait pas les poser). Secrets, images envoyées et certificat
# ne sont pas touchés.
fix_perms() {
  remote "find $1 \( -path $1/.well-known -o -path $1/public/media -o -path $1/var -o -path $1/vendor \) -prune -o \
    -type d -exec chmod 755 {} + -o -type f ! -name .env.local -exec chmod 644 {} +"
}

# Ce qui part est l'état de ce poste (fichiers suivis par Git et nouveaux fichiers non ignorés) :
# on prévient s'il diffère du dernier commit.
check_clean_tree() {
  if [ -n "$(git status --porcelain -- apps packages)" ]; then
    git status --short -- apps packages
    confirm 'Ces modifications ne sont pas commitées et seront déployées. Continuer ?'
  fi
}

deploy_api() {
  local build
  build="$(mktemp -d)"
  trap 'rm -rf "$build"' RETURN

  # Code envoyé tel quel (sans vendor/) : les dépendances s'installent sur le serveur.
  log "API : préparation (dernier commit : $(git rev-parse --short HEAD))…"
  git ls-files -z --cached --others --exclude-standard -- apps/api |
    while IFS= read -r -d '' file; do [ -e "$file" ] && printf '%s\0' "$file"; done |
    tar --null -T - -cf - | tar -x -C "$build" --strip-components=2
  rm -rf "$build/tests" "$build/bin/phpunit" "$build/.env.test" "$build/phpunit.dist.xml" "$build/phpstan.dist.neon" "$build/.php-cs-fixer.dist.php"

  # Règle de l'hébergeur : aucune vulnérabilité connue dans les dépendances.
  log 'Audit des dépendances…'
  docker image inspect gaw-api:dev >/dev/null 2>&1 || fail 'Image gaw-api:dev absente : docker compose build api.'
  docker run --rm -v "$build:/app:ro" -w /app gaw-api:dev composer audit --locked --no-dev --no-interaction ||
    fail 'composer audit signale une vulnérabilité : rien n’a été envoyé.'

  # Fichiers du serveur jamais écrasés : secrets, cache, dépendances, images envoyées.
  local excludes=(--exclude=/.env.local --exclude=/var/ --exclude=/vendor/ --exclude=/public/bundles/
    --exclude=/public/media/)
  if $DRY_RUN; then
    log 'Simulation : fichiers de l’API qui changeraient.'
    rsync -azn --delete --itemize-changes "${excludes[@]}" "$build/" "$SSH_HOST:$API_DIR/" | head -50
    return
  fi

  remote "test -f $API_DIR/.env.local" || fail "$API_DIR/.env.local absent sur le serveur (modèle : infra/apache/env.local.example)."
  # Garde-fous : jamais de valeurs de développement en production.
  remote "cd $API_DIR && perms=\$(stat -c %a .env.local) && [ \"\$perms\" = 600 ]" ||
    fail "$API_DIR/.env.local doit avoir les droits 600."
  remote "cd $API_DIR && grep -qE '^APP_ENV=prod\$' .env.local && grep -qE '^APP_DEBUG=0\$' .env.local &&
    grep -qE '^APP_SECRET=.+' .env.local && ! grep -qE '^TURNSTILE_SECRET_KEY=[123]x0000' .env.local &&
    ! grep -qE '^MAILER_DSN=(null://|\$)' .env.local" ||
    fail '.env.local : APP_ENV=prod, APP_DEBUG=0, APP_SECRET, pas de clé Turnstile de test et un MAILER_DSN actif sont obligatoires.'
  # Sans clé Turnstile, le formulaire reste protégé par le piège, le délai et la limite d'envois.
  remote "grep -qE '^TURNSTILE_SECRET_KEY=.+' $API_DIR/.env.local" ||
    log 'Attention : TURNSTILE_SECRET_KEY vide, Turnstile désactivé (TURNSTILE_SITE_KEY doit être vide aussi).'

  # 1. Sauvegarde de la base sur le serveur (~/backups, trois gardées, format de l'hébergeur),
  #    puis copie vérifiée sur ce poste.
  local name backup
  name="gaw_$(date +%Y%m%d_%H%M%S).sql.gz"
  log "Sauvegarde de la base → ~/backups/$name"
  remote "set -o pipefail && mkdir -p ~/backups && chmod 700 ~/backups &&
    db=\$(sed -n 's#^DATABASE_URL=.*/\\([^/?\"]*\\).*#\\1#p' $API_DIR/.env.local) && [ -n \"\$db\" ] &&
    mariadb-dump --single-transaction --routines --triggers \"\$db\" | gzip > ~/backups/$name &&
    chmod 600 ~/backups/$name &&
    ls -1t ~/backups/gaw_*.sql.gz | tail -n +4 | xargs -r rm --" ||
    fail 'Sauvegarde en échec : rien n’a été modifié.'
  mkdir -p "$BACKUP_DIR"
  chmod 700 "$BACKUP_DIR"
  backup="$BACKUP_DIR/$name"
  rsync -a "$SSH_HOST:backups/$name" "$backup"
  chmod 600 "$backup"
  gzip -t "$backup" || fail 'Sauvegarde illisible : rien n’a été modifié.'
  gunzip -c "$backup" | tail -n 1 | grep -q 'Dump completed' || fail 'Sauvegarde incomplète : rien n’a été modifié.'

  # 2. Envoi du code, puis droits 644/755.
  log 'Envoi du code…'
  rsync -az --delete "${excludes[@]}" "$build/" "$SSH_HOST:$API_DIR/"
  fix_perms "$API_DIR"

  # 3. Sur le serveur, dans l'ordre imposé : dépendances, migrations (elles ne font qu'ajouter),
  #    cache, arrêt du worker (relancé seul sur le nouveau code).
  log 'Dépendances, migrations, cache…'
  remote "set -e
    umask 022
    cd $API_DIR
    mkdir -p public/media
    composer install --no-dev --optimize-autoloader --classmap-authoritative --no-scripts --no-progress --no-interaction -q
    php bin/console assets:install public -q
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
    php bin/console cache:clear -q
    php bin/console messenger:stop-workers -q" ||
    fail "Mise à jour en échec sur le serveur. Sauvegarde : $backup. Journaux : ~/logs/php_log, ~/logs/error_log."

  # 4. Santé.
  log "Vérification de $API_URL/api/v1/health…"
  local status=''
  for _ in 1 2 3 4 5; do
    status="$(curl -fsS --max-time 10 "$API_URL/api/v1/health" 2>/dev/null || true)"
    [[ "$status" == *'"ok"'* ]] && break
    sleep 3
  done
  [[ "$status" == *'"ok"'* ]] ||
    fail "API en échec ($API_URL/api/v1/health). Sauvegarde : $backup. Journaux : ~/logs/php_log, ~/logs/error_log."
  log 'API en service. Worker : ssh '"$SSH_HOST"' systemctl status viraka-gaw-messenger'
}

deploy_site() {
  # Clé de site et clé secrète vont ensemble : l'une sans l'autre bloquerait tout envoi.
  local secret_set=false
  remote "grep -qE '^TURNSTILE_SECRET_KEY=.+' $API_DIR/.env.local" && secret_set=true
  if { [ -n "$TURNSTILE_SITE_KEY" ] && ! $secret_set; } || { [ -z "$TURNSTILE_SITE_KEY" ] && $secret_set; }; then
    fail 'Turnstile : renseigner la clé de site (deploy.env) et la clé secrète (.env.local) ensemble, ou aucune des deux.'
  fi

  log "Site : compilation avec le contenu de ${API_URL}…"
  (cd apps/web && PUBLIC_API_URL="$API_URL" PUBLIC_SITE_URL="$SITE_URL" PUBLIC_TURNSTILE_SITE_KEY="$TURNSTILE_SITE_KEY" pnpm build)
  [ -f apps/web/dist/index.html ] && [ -f apps/web/dist/.htaccess ] || fail 'Compilation incomplète.'

  # Le dossier web contient encore l'ancien WordPress : jamais remplacé sans demande explicite.
  if remote "test -f $SITE_DIR/wp-config.php" && ! $DRY_RUN && ! $REPLACE_WORDPRESS; then
    printf '%s contient encore WordPress. Sauvegardez-le d’abord sur ce poste :\n' "$SITE_DIR"
    printf '  rsync -avz %s:%s/ ancien_site/public_html/\n' "$SSH_HOST" "$SITE_DIR"
    printf '  ssh %s "cd %s && wp db export - | gzip" > ancien_site/wordpress.sql.gz\n' "$SSH_HOST" "$SITE_DIR"
    fail 'Puis relancez avec --replace-wordpress.'
  fi

  # Fichiers du serveur à ne jamais toucher : certificat, validation Google Search Console,
  # outils et statistiques de visites de l'hébergeur.
  local rules=(--exclude=/.well-known/ --exclude='/google*.html' --exclude=/phpmyadmin/ --exclude=/roundcube/
    --exclude=/stats/ --exclude=/_headers)
  log 'Simulation : fichiers du site qui changeraient.'
  rsync -azn --delete --itemize-changes "${rules[@]}" apps/web/dist/ "$SSH_HOST:$SITE_DIR/" | grep -E '^(\*deleting|<)' | head -40 || true
  $DRY_RUN && return
  confirm 'Publier ?'
  rsync -az --delete "${rules[@]}" apps/web/dist/ "$SSH_HOST:$SITE_DIR/"
  fix_perms "$SITE_DIR"

  local code
  code="$(curl -s -o /dev/null -w '%{http_code}' --max-time 10 "$SITE_URL/")"
  [ "$code" = 200 ] || fail "Le site répond $code sur $SITE_URL/."
  log "Site publié : $SITE_URL/"
}

check_clean_tree
case "$TARGET" in
  api) deploy_api ;;
  site) deploy_site ;;
  all) deploy_api && deploy_site ;;
esac
