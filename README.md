# Genius At Work — site 2026

Monorepo de la refonte de [genius-at-work.com](https://genius-at-work.com/).

| Dossier             | Contenu                                                                     |
| ------------------- | --------------------------------------------------------------------------- |
| `apps/web`          | Site public : Astro 7, TypeScript, React (islands), Tailwind CSS 4, GSAP    |
| `apps/api`          | API Symfony 7.4 : contact, e-mails, RGPD, back-office et contenu du site    |
| `packages/config`   | Tokens du design system, configuration TypeScript partagée                  |
| `packages/types`    | Schéma de validation du formulaire de contact, partagé par le site et l'API |
| `infra/docker`      | Image FrankenPHP de l'API, stack de production                              |
| `infra/cloudflare`  | Configuration Wrangler du site statique                                     |
| `infra/deploy`      | Script de déploiement de l'API                                              |
| `.github/workflows` | CI et déploiement                                                           |
| `docs/`             | Audit de contenu, propositions DA et architecture, guide de déploiement     |

## Démarrer

Prérequis : Node 24 (`nvm use`), pnpm 10, Docker.

```sh
pnpm install
docker compose up -d --wait     # API, worker, scheduler, MySQL, Valkey, Mailpit
docker compose exec api php bin/console doctrine:migrations:migrate -n
docker compose exec api php bin/console app:content:import    # contenu initial du site
pnpm --filter @gaw/web dev      # http://localhost:4321
```

Le contenu du site est géré dans le back-office (http://localhost:8088/admin) et lu sur l'API :
en développement, une modification enregistrée recharge la page ouverte. Sans API, le site ne
démarre pas (message explicite) ; `CONTENT_SNAPSHOT` permet de construire sans elle (CI).

| Adresse                             | Service                                     |
| ----------------------------------- | ------------------------------------------- |
| http://localhost:4321               | Site                                        |
| http://localhost:8088/api/v1/health | API (documentation : `/api/docs`)           |
| http://localhost:8088/admin         | Back-office des demandes de contact         |
| http://localhost:8025               | Mailpit : tous les e-mails envoyés en local |
| `127.0.0.1:3307` (`gaw` / `gaw`)    | MySQL                                       |

Les ports se changent avec `API_PORT`, `MYSQL_PORT` et `MAILPIT_PORT`.

| Commande                                             | Rôle                                 |
| ---------------------------------------------------- | ------------------------------------ |
| `pnpm build`                                         | Build de production du site          |
| `pnpm check`                                         | Vérification des types (astro check) |
| `pnpm lint`                                          | ESLint                               |
| `pnpm format`                                        | Prettier                             |
| `docker compose exec api bin/phpunit`                | Tests de l'API                       |
| `docker compose exec api vendor/bin/phpstan analyse` | Analyse statique de l'API            |

Styleguide vivant : `/design-system/` (non indexé, hors sitemap).

SEO et sécurité du site (générés au build) :

- CSP par empreintes dans une `<meta>` de chaque page (`astro.config.mjs`, `security.csp`) ;
  `frame-ancestors` et les autres en-têtes dans `apps/web/public/_headers` ;
- cartes Open Graph 1200 × 630 par page indexable (`/og/…png`, `src/lib/seo/og.ts`) ;
- JSON-LD : `Organization` et `WebSite` (accueil), `BreadcrumbList`, `Service` ;
- `robots.txt` et sitemap bilingue avec `hreflang`.

## Documentation

- `docs/content-audit.md` : inventaire du site actuel, la source de vérité du contenu
- `docs/proposal/01-direction-artistique.md` : première direction artistique (« Cyanotype », remplacée)
- `docs/proposal/03-refonte-visuelle.md` : direction actuelle « Signal » (palette, logo), design system
- `docs/proposal/02-architecture-technique.md` : architecture, données, SEO, performance, parcours
- `docs/back-office.md` : back-office (demandes, contenu du site), comptes, sécurité
- `docs/deploiement.md` : mise en production, règles du pipeline, opérations courantes
