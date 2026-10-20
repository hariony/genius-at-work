#syntax=docker/dockerfile:1

# Image de l'API Symfony : FrankenPHP en mode worker (Symfony 7.4 le gère nativement).
# Contexte de build : apps/api ; contexte additionnel « docker » : ce dossier.
#   docker build --build-context docker=infra/docker/api -f infra/docker/api/Dockerfile --target prod apps/api

FROM dunglas/frankenphp:1.13-php8.4 AS base

SHELL ["/bin/bash", "-euxo", "pipefail", "-c"]

WORKDIR /app

# hadolint ignore=DL3008
RUN <<-SH
	apt-get update
	apt-get install -y --no-install-recommends file
	install-php-extensions @composer apcu intl opcache pdo_mysql redis zip
	rm -rf /var/lib/apt/lists/*
SH

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PHP_INI_SCAN_DIR=":$PHP_INI_DIR/app.conf.d"
# HTTP en clair sur 8080 : TLS est terminé par Cloudflare, le Tunnel parle au conteneur.
ENV SERVER_NAME=:8080

COPY --link --from=docker conf.d/10-app.ini $PHP_INI_DIR/app.conf.d/
COPY --link --from=docker --chmod=755 docker-entrypoint.sh /usr/local/bin/docker-entrypoint
COPY --link --from=docker Caddyfile /etc/frankenphp/Caddyfile

ENTRYPOINT ["docker-entrypoint"]
HEALTHCHECK --start-period=30s --interval=15s CMD php -r 'exit(false === @file_get_contents("http://localhost:8080/api/v1/health", context: stream_context_create(["http" => ["timeout" => 5]])) ? 1 : 0);'
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]


# Développement : sources montées depuis l'hôte, rechargement à chaque modification.
FROM base AS dev

ENV APP_ENV=dev
ENV FRANKENPHP_WORKER_CONFIG=watch

# hadolint ignore=DL3008
RUN <<-SH
	mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
	apt-get update
	apt-get install -y --no-install-recommends git unzip
	rm -rf /var/lib/apt/lists/*
	git config --system --add safe.directory /app
SH

COPY --link --from=docker conf.d/20-app.dev.ini $PHP_INI_DIR/app.conf.d/

CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile", "--watch"]


# Production : dépendances sans les outils de dev, autoload optimisé, exécution sans root.
FROM base AS prod

ENV APP_ENV=prod

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY --link --from=docker conf.d/20-app.prod.ini $PHP_INI_DIR/app.conf.d/

# Couche des dépendances : reconstruite seulement si composer.* change.
COPY --link composer.json composer.lock symfony.lock ./
RUN composer install --no-cache --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress

COPY --link . ./

RUN <<-SH
	mkdir -p var/cache var/log var/share public/media
	composer dump-autoload --classmap-authoritative --no-dev
	composer dump-env prod
	composer run-script --no-dev post-install-cmd
	chmod +x bin/console
	# FrankenPHP (Caddy) écrit son état dans /data et /config.
	mkdir -p /data/caddy /config/caddy
	# public/media : images du back-office (volume en production, initialisé avec ce propriétaire).
	chown -R www-data:www-data var public/media /data /config
SH

USER www-data
