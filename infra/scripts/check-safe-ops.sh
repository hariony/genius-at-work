#!/usr/bin/env bash
# shellcheck disable=SC1112 # apostrophes typographiques françaises dans les messages.
# Garde-fou de la CI : refuse toute commande destructive dans les fichiers qui pilotent
# l'infrastructure et le déploiement (workflows, Docker, scripts, configuration Symfony).
# Les lignes de commentaire sont ignorées. Usage : infra/scripts/check-safe-ops.sh
set -euo pipefail

cd "$(dirname "$0")/../.."

FORBIDDEN=(
  'down[[:space:]]+(-v|--volumes)'
  'volume[[:space:]]+(rm|prune)'
  'system[[:space:]]+prune'
  'doctrine:database:drop'
  'doctrine:schema:(drop|update[^#]*--force)'
  'doctrine:fixtures:load'
  'migrations:(migrate[[:space:]]+(prev|first)|execute[^#]*--down)'
  'DROP[[:space:]]+(DATABASE|SCHEMA)'
  'TRUNCATE[[:space:]]'
  'FLUSHALL|FLUSHDB'
)

TARGETS=(.github compose.yaml infra apps/api/config apps/api/bin)
pattern="$(IFS='|'; echo "${FORBIDDEN[*]}")"

matches="$(grep -rnIE "$pattern" "${TARGETS[@]}" 2>/dev/null \
  | grep -v '^infra/scripts/check-safe-ops.sh:' \
  | grep -vE '^[^:]+:[0-9]+:[[:space:]]*#' || true)"

if [ -n "$matches" ]; then
  echo 'Commande destructive détectée :' >&2
  echo "$matches" >&2
  exit 1
fi

echo 'Aucune commande destructive dans les fichiers d’infrastructure.'
