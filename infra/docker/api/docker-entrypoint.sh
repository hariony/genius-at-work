#!/bin/sh
set -e

# Point d'entrée commun à l'API (frankenphp), au worker et au scheduler (php bin/console …).
if [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then
	if [ "$APP_ENV" = 'prod' ]; then
		# Garde-fous : on refuse de démarrer en production avec des valeurs de développement.
		fail() { echo "Configuration de production invalide : $1" >&2; exit 1; }
		[ -n "$APP_SECRET" ] || fail 'APP_SECRET est vide.'
		case "$TURNSTILE_SECRET_KEY" in
			''|1x0000*|2x0000*|3x0000*) fail 'TURNSTILE_SECRET_KEY est vide ou est une clé de test.' ;;
		esac
		case "$MAILER_DSN" in
			''|null://*) fail "MAILER_DSN n'envoie aucun e-mail." ;;
		esac
	elif [ -z "$(ls -A vendor/ 2>/dev/null)" ]; then
		composer install --prefer-dist --no-progress --no-interaction
	fi

	echo 'Attente de la base de données…'
	attempts=60
	until php bin/console dbal:run-sql -q 'SELECT 1' >/dev/null 2>&1; do
		attempts=$((attempts - 1))
		if [ "$attempts" -eq 0 ]; then
			echo 'Base de données injoignable.' >&2
			exit 1
		fi
		sleep 1
	done

	# En développement uniquement : les migrations s'appliquent au démarrage.
	# En production, seul le script de déploiement les lance, après une sauvegarde.
	if [ "$APP_ENV" = 'dev' ] && [ "$1" = 'frankenphp' ]; then
		php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
	fi
fi

exec docker-php-entrypoint "$@"
