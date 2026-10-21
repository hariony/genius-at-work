#!/usr/bin/env bash
# shellcheck disable=SC1112 # apostrophes typographiques françaises dans les messages.
# Mise en production en une commande : commit (facultatif), push sur GitHub, puis déploiement
# sur le serveur (infra/apache/deploy.sh). Raccourci : commande « mep » (docs/deploiement-apache.md).
#
#   mep                       # tout est déjà commité : push puis déploiement complet (api + site)
#   mep "Message de commit"   # commite toutes les modifications, push, déploiement complet
#   mep site "Message"        # cible : api, site ou all (par défaut)
#
# Le déploiement demande toujours si l'hébergeur a été prévenu la veille.
set -euo pipefail

# Lancé par un lien (ex. ~/.local/bin/mep) : on remonte au vrai fichier pour trouver le dépôt.
self="$0"
while [ -L "$self" ]; do
  target="$(readlink "$self")"
  case "$target" in /*) self="$target" ;; *) self="$(dirname "$self")/$target" ;; esac
done
cd "$(dirname "$self")/../.."

log() { printf '\033[1m→ %s\033[0m\n' "$*"; }
fail() { printf '\033[31m✗ %s\033[0m\n' "$*" >&2; exit 1; }

TARGET=all
case "${1:-}" in api | site | all) TARGET="$1"; shift ;; esac
MESSAGE="${1:-}"

[ "$(git branch --show-current)" = main ] || fail 'Seule la branche main part en production.'

if [ -n "$(git status --porcelain)" ]; then
  git status --short
  [ -n "$MESSAGE" ] || fail 'Modifications non commitées : relancer avec un message, ex. mep "Corrige la page contact".'
  log 'Commit…'
  git add -A
  git commit -q -m "$MESSAGE"
fi

log "Push de $(git rev-parse --short HEAD) sur GitHub…"
git push origin main

# Node 24 pour la compilation du site (nvm, s'il est installé).
if [ -s "$HOME/.nvm/nvm.sh" ]; then
  # shellcheck source=/dev/null
  source "$HOME/.nvm/nvm.sh" >/dev/null
  nvm use 24 >/dev/null
fi

log "Déploiement ($TARGET)…"
exec infra/apache/deploy.sh "$TARGET" --yes
