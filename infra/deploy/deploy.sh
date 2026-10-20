#!/usr/bin/env bash
# shellcheck disable=SC1112 # apostrophes typographiques françaises dans les messages.
# Déploiement de l'API sur le serveur de production. Lancé par GitHub Actions (via SSH)
# depuis /srv/gaw, ou à la main : ./deploy.sh ghcr.io/<owner>/gaw-api:<sha>
#
# Ordre : image → sauvegarde de la base → migrations → redémarrage → contrôle de santé.
# Règles :
#   - aucune commande destructive (pas de suppression de base, de volume ni de schéma) ;
#   - la base est sauvegardée avant chaque migration ;
#   - si les nouveaux conteneurs ne sont pas sains, l'image précédente est remise en service.
#     Les migrations ne sont pas annulées : elles doivent rester compatibles avec la version
#     précédente du code (ajouts d'abord, suppressions dans un déploiement ultérieur).
set -Eeuo pipefail

NEW_IMAGE="${1:?Usage : deploy.sh <image>}"
cd "$(dirname "$(readlink -f "$0")")"

COMPOSE=(docker compose -f compose.prod.yaml)
CURRENT_FILE=.current-image
PREVIOUS_IMAGE="$(cat "$CURRENT_FILE" 2>/dev/null || true)"
HEALTH_URL="${HEALTH_URL:-https://api.genius-at-work.com/api/v1/health}"

log() { printf '[deploy %s] %s\n' "$(date -u +%H:%M:%S)" "$*"; }

# Un seul déploiement à la fois (flock : paquet util-linux, présent sur Debian/Ubuntu).
command -v flock >/dev/null || { log 'flock est introuvable.'; exit 1; }
exec 9>.deploy.lock
flock -n 9 || { log 'Un déploiement est déjà en cours.'; exit 1; }

[ -f .env ] || { log 'Fichier .env absent (voir prod.env.example).'; exit 1; }

log "Image demandée : $NEW_IMAGE"
log "Image en service : ${PREVIOUS_IMAGE:-aucune (premier déploiement)}"
export API_IMAGE="$NEW_IMAGE"

log 'Téléchargement de l’image…'
"${COMPOSE[@]}" pull api

log 'Démarrage de MySQL et Valkey (sans effet s’ils tournent déjà)…'
"${COMPOSE[@]}" up -d --wait mysql redis

# 1. Sauvegarde, vérifiée avant de continuer.
mkdir -p backups
chmod 700 backups
backup="backups/pre-deploy-$(date -u +%Y%m%dT%H%M%SZ).sql.gz"
log "Sauvegarde de la base : $backup"
# shellcheck disable=SC2016 # variables développées dans le conteneur MySQL, pas ici.
"${COMPOSE[@]}" exec -T mysql sh -c \
  'exec mysqldump --single-transaction --routines --triggers --no-tablespaces -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' \
  | gzip > "$backup"
gzip -t "$backup"
gzip -dc "$backup" | tail -n 1 | grep -q 'Dump completed' || { log 'Sauvegarde incomplète : arrêt.'; exit 1; }
chmod 600 "$backup"

# 2. Migrations avec la nouvelle image, avant de la mettre en service.
#    En cas d'échec, l'ancienne version continue de tourner.
log 'Migrations Doctrine…'
"${COMPOSE[@]}" run --rm --no-deps api \
  php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# Contenu initial du site : n'écrit que les contenus encore absents (premier déploiement, nouvelle
# section du back-office). Ne remplace jamais une modification faite dans le back-office.
log 'Contenu initial (contenus absents uniquement)…'
"${COMPOSE[@]}" run --rm --no-deps api php bin/console app:content:import data/content-seed.json

# 3. Mise en service, avec retour arrière si la santé n'est pas confirmée.
rollback() {
  if [ -n "$PREVIOUS_IMAGE" ]; then
    log "Échec : retour à $PREVIOUS_IMAGE"
    API_IMAGE="$PREVIOUS_IMAGE" "${COMPOSE[@]}" up -d --wait --wait-timeout 180 api worker scheduler cloudflared \
      || log 'Le retour arrière a échoué aussi : intervention manuelle requise.'
  else
    log 'Échec du premier déploiement : aucune version précédente à restaurer.'
  fi
  exit 1
}
trap rollback ERR

log 'Redémarrage de l’API, du worker et du scheduler…'
"${COMPOSE[@]}" up -d --wait --wait-timeout 180 api worker scheduler cloudflared

log "Contrôle de santé public : $HEALTH_URL"
ok=0
for _ in $(seq 1 12); do
  if curl -fsS --max-time 5 "$HEALTH_URL" | grep -q '"ok"'; then ok=1; break; fi
  sleep 5
done
[ "$ok" -eq 1 ]

trap - ERR
echo "$NEW_IMAGE" > "$CURRENT_FILE"
printf '%s %s\n' "$(date -u +%FT%TZ)" "$NEW_IMAGE" >> deploy-history.log
log 'Déploiement terminé.'
