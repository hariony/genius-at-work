# Proposition — Architecture technique, données, SEO, parcours

> **Mise à jour (octobre 2026)** : Sanity est abandonné. Le contenu éditorial est géré dans le
> back-office de l'API Symfony et lu par le site au build : voir `docs/back-office.md`. Les
> mentions de Sanity ci-dessous décrivent la proposition initiale.

> Statut : **proposition à valider** — aucune implémentation.
> Couvre les points 8 à 12 et 15 à 17 de la demande. Versions vérifiées dans la documentation officielle (octobre 2026).

---

## Vue d'ensemble

```
                         ┌──────────────── Cloudflare ────────────────┐
 Visiteur ──HTTPS──▶     │ DNS · SSL · WAF · CDN · Turnstile · Redirects │
                         └───────┬───────────────────────┬────────────┘
                                 │                       │
                   genius-at-work.com              api.genius-at-work.com
                   (site statique Astro,           (Cloudflare Tunnel → VPS UE)
                    Workers Static Assets)               │
                                 │              ┌────────┴─────────────────────┐
             build ◀── Sanity (contenu)         │ Docker                        │
             (GROQ, webhook → rebuild)          │  api      FrankenPHP/Symfony  │
                                 │              │  worker   messenger:consume   │
              POST /api/v1/contact-requests ──▶ │  scheduler                    │
                                                │  mysql    8.4 LTS             │
                                                │  redis    cache/queue/limiter │
                                                └───────────────────────────────┘
                                          admin.genius-at-work.com → EasyAdmin (Cloudflare Access + login + 2FA)
```

| Brique         | Responsabilité                                                                             | Ne fait pas                            |
| -------------- | ------------------------------------------------------------------------------------------ | -------------------------------------- |
| **Astro**      | Présentation, rendu statique, SEO, i18n, animations, formulaire (UI + validation client)   | Aucune logique métier, aucun secret    |
| **Sanity**     | Contenu éditorial : projets, services, technologies, pages, équipe, journal, réglages, SEO | Aucune donnée personnelle de prospects |
| **Symfony**    | Logique métier : leads, CRM (clients, opportunités, notes), formulaires, emails, admin     | Aucun rendu des pages publiques        |
| **MySQL**      | Données persistantes du backend                                                            | —                                      |
| **Redis**      | Cache applicatif, transport Messenger, rate limiting, sessions admin                       | Pas de données durables                |
| **Cloudflare** | DNS, CDN, SSL, WAF, anti-bot, en-têtes de sécurité, redirections 301                       | —                                      |

**Choix structurant** : le site public est **100 % statique** (SSG). Toutes les pages sont pré-rendues au build à partir de Sanity, puis servies depuis le CDN Cloudflare. Le seul appel dynamique est l'envoi du formulaire vers l'API. On obtient la meilleure performance possible, une surface d'attaque minimale et un site qui reste en ligne même si l'API tombe.
Une publication dans Sanity déclenche un webhook → GitHub Actions `repository_dispatch` → build → déploiement (environ 2 min).

---

## 8. Architecture frontend

### Monorepo

```
/
├── apps/
│   ├── web/                 # Astro (site public)
│   └── api/                 # Symfony (API + admin)
├── cms/
│   └── sanity/              # Sanity Studio + schémas
├── packages/
│   ├── config/              # tsconfig, eslint, prettier, tokens Tailwind partagés
│   ├── types/               # Types TS générés depuis les schémas Sanity (sanity typegen) + DTO API
│   └── ui/                  # Primitives sans dépendance framework (icônes SVG, tokens) si réutilisées
├── infra/
│   ├── docker/              # Dockerfiles, conf FrankenPHP/Caddy, MySQL, Redis
│   └── cloudflare/          # wrangler.toml, _headers, _redirects, règles WAF (as code)
├── docs/
├── .github/workflows/
├── docker-compose.yml       # + docker-compose.dev.yml / docker-compose.prod.yml
├── pnpm-workspace.yaml
└── turbo.json               # cache des tâches lint/build/test
```

Outils : **pnpm workspaces** + **Turborepo** (builds incrémentaux, cache CI).

### `apps/web/src`

```
src/
├── pages/                     # Routage uniquement : fichiers fins, ils délèguent aux sections
│   ├── index.astro
│   ├── services/index.astro · [slug].astro
│   ├── projects/index.astro · [slug].astro
│   ├── about.astro · technology.astro · contact.astro
│   ├── legal.astro · privacy-policy.astro · 404.astro
│   ├── journal/ (phase 2)
│   ├── en/…                   # même arborescence, mêmes composants, locale = 'en'
│   ├── og/[...route].png.ts   # images Open Graph générées au build
│   └── robots.txt.ts
├── layouts/
│   ├── BaseLayout.astro       # <head>, SEO, hreflang, JSON-LD, ClientRouter, polices
│   └── PageLayout.astro       # nav + main + footer + overlay de transition
├── sections/                  # Blocs de page composés (HomeHero, SelectedWork, EngineeringStack, Method…)
├── components/
│   ├── ui/                    # Primitives : Button, Link, Tag, Container, Grid, Heading, Media, Icon
│   ├── navigation/            # SiteHeader, MenuOverlay, LanguageSwitcher, Breadcrumbs
│   ├── project/               # ProjectCard, ProjectMeta, ProjectGallery, ProjectFilters (island)
│   ├── service/               # ServiceIndex, ServiceRow
│   ├── contact/               # ContactForm (island React), OfficeCard, LocalClock
│   └── seo/                   # SeoHead, JsonLd
├── animations/                # ⟵ tout GSAP est ici, nulle part ailleurs
│   ├── core/                  # gsap.ts (enregistrement des plugins), motion.ts (matchMedia, reduced motion), lifecycle.ts
│   ├── presets/               # reveal-lines.ts, reveal-image.ts, parallax.ts, counter.ts, magnetic.ts…
│   ├── scenes/                # hero.ts, selected-work.ts (pin horizontal), engineering-diagram.ts, method.ts
│   ├── transitions/           # page-curtain.ts
│   └── controller.ts          # lit data-anim / data-scene, instancie et nettoie
├── hooks/                     # Hooks React des islands uniquement (useFormState, useReducedMotion)
├── lib/
│   ├── i18n/                  # config, dictionnaires UI (fr.ts, en.ts), helpers de route
│   ├── seo/                   # builders JSON-LD, meta, canonical/hreflang
│   ├── sanity/                # client, requêtes GROQ typées, helpers d'image
│   └── utils/
├── services/                  # Accès aux données : projectsService.getFeatured(locale)… (seule couche qui parle à Sanity/API)
├── types/                     # Types de domaine front (Project, Service, Locale…)
├── content/                   # Données statiques versionnées : dictionnaires UI, fallback de build
└── styles/
    ├── tokens.css             # @theme Tailwind v4 (importé de packages/config)
    ├── base.css · typography.css · utilities.css
```

### Règles

- **Dépendances unidirectionnelles** : `pages → sections → components → lib/services → types`. Un composant n'importe jamais `services` ; il reçoit ses données en props. Ces règles sont imposées par ESLint (`import/no-restricted-paths`).
- **React uniquement pour les islands** qui ont un état réel :
  | Island                                                          | Directive                                      |
  | --------------------------------------------------------------- | ---------------------------------------------- |
  | `ContactForm` (multi-étapes, validation, envoi)                 | `client:visible`                               |
  | `ProjectFilters` (filtres + recherche, synchronisés avec l'URL) | `client:idle`                                  |
  | Tout le reste (nav, menu, horloges, animations)                 | Astro + `<script>` TS natif, **0 hydratation** |
- **Contenu** : aucun texte en dur dans les composants. Le contenu vient de Sanity, les libellés d'interface des dictionnaires `lib/i18n`.
- **Validation partagée** : schéma **Zod** du formulaire dans `packages/types`, utilisé côté client ; les mêmes règles sont réécrites côté Symfony avec le Validator (le serveur fait foi).

---

## 9. Architecture backend Symfony

**Symfony 7.4 LTS** (support jusqu'en novembre 2029) sur **PHP 8.4**, **API Platform 4**, **Doctrine ORM 3**, servi par **FrankenPHP** en mode worker. La montée vers Symfony 8.4 LTS est prévue à sa sortie (fin 2027).

### Couches (Clean Architecture, imposées par Deptrac en CI)

```
apps/api/src/
├── Domain/                          # PHP pur : aucune dépendance Symfony ni Doctrine
│   ├── Lead/
│   │   ├── Lead.php                 # Agrégat : qualify(), markWon(), anonymize()…
│   │   ├── LeadId.php · LeadStatus.php (enum) · ProjectType.php (enum) · BudgetRange.php (enum)
│   │   ├── LeadRepository.php       # Interface (port)
│   │   └── Event/LeadSubmitted.php
│   ├── Client/ (Client, ClientContact, ClientRepository)
│   ├── Opportunity/ (Opportunity, Stage, Note)
│   └── Shared/ (Email, PhoneNumber, Locale, Clock interface, DomainEvent)
├── Application/
│   ├── Contact/SubmitContactRequest/
│   │   ├── SubmitContactRequestCommand.php
│   │   └── SubmitContactRequestHandler.php      # orchestre : anti-spam → Lead → persist → event
│   ├── Contact/Port/ (SpamGuard, BotVerifier)
│   ├── Notification/ (NotifyTeamOnLeadSubmitted, AcknowledgeLeadSubmission)  # handlers asynchrones
│   ├── Privacy/PurgeExpiredLeads/
│   └── Crm/ (QualifyLead, ConvertLeadToClient, AddNote…)
├── Infrastructure/
│   ├── Persistence/Doctrine/ (Mapping/*.orm.xml, DoctrineLeadRepository…, Types/)
│   ├── Messaging/ (configuration Messenger, middleware)
│   ├── Mailer/ (SymfonyMailerNotifier, templates Twig FR/EN)
│   ├── Security/ (TurnstileVerifier, HoneypotSpamGuard, RateLimiterGuard)
│   └── Clock/
├── Presentation/
│   ├── Api/
│   │   ├── Resource/ContactRequestInput.php     # DTO API Platform + contraintes Validator
│   │   ├── State/ContactRequestProcessor.php    # DTO → Command → bus. Aucune logique métier
│   │   └── Resource/Health.php
│   ├── Admin/ (DashboardController, LeadCrudController… EasyAdmin)
│   └── Cli/ (PurgeExpiredLeadsCommand, CreateAdminUserCommand)
└── Kernel.php
```

**Contrôleur = orchestration** : le `State Processor` transforme le DTO en commande et la dispatch. Les règles métier vivent dans le Domain, la coordination dans l'Application, les détails techniques dans l'Infrastructure.

### Flux du formulaire

```
ContactForm (island)
  │  validation Zod + jeton Turnstile + honeypot + horodatage
  ▼
Cloudflare (WAF, rate limit en périphérie)
  ▼
POST /api/v1/contact-requests  (API Platform, JSON)
  ▼
ContactRequestInput ── Symfony Validator ── 422 avec erreurs par champ
  ▼
ContactRequestProcessor → command.bus (synchrone)
  ▼
SubmitContactRequestHandler
  ├─ RateLimiter (sliding window, Redis) : 5 envois / heure / IP, 20 / jour
  ├─ SpamGuard : honeypot rempli · envoi < 3 s · liens multiples
  ├─ BotVerifier : vérification serveur du jeton Turnstile
  ├─ Lead::submit(...) → LeadRepository::save()            (MySQL)
  └─ dispatch LeadSubmitted → event.bus (asynchrone, transport Redis)
        ├─ NotifyTeamOnLeadSubmitted  → Mailer → équipe (FR)
        └─ AcknowledgeLeadSubmission  → Mailer → prospect (dans sa langue)
  ▼
202 Accepted { reference }        ← réponse immédiate, les emails partent en arrière-plan
```

Retries Messenger : 3 tentatives avec backoff exponentiel, puis transport `failed` (Doctrine) visible dans l'admin.

### Endpoints

| Méthode | Route                              | Rôle                                                                           | Auth                                   |
| ------- | ---------------------------------- | ------------------------------------------------------------------------------ | -------------------------------------- |
| POST    | `/api/v1/contact-requests`         | Créer une demande de contact                                                   | Publique (Turnstile + rate limit)      |
| GET     | `/api/v1/health`                   | Santé : base, Redis, file d'attente                                            | Publique (réponse minimale)            |
| POST    | `/api/v1/newsletter-subscriptions` | Phase 2 : double opt-in                                                        | Publique                               |
| —       | `/admin/*`                         | Back-office EasyAdmin (leads, clients, opportunités, notes, messages en échec) | Session + 2FA TOTP + Cloudflare Access |

Les projets ne sont **pas** exposés par l'API : ils viennent de Sanity. Pas de JWT ni d'OAuth : aucun client tiers ne s'authentifie sur l'API. Le besoin sera réévalué si un espace client est demandé.

### Sécurité backend

CORS strict (seules les origines du site), en-têtes de sécurité, sessions admin en Redis avec cookies `Secure` / `HttpOnly` / `SameSite=Strict`, CSRF sur l'admin, mots de passe hashés avec l'algorithme `auto` (bcrypt/argon2), secrets via Symfony Secrets ou variables d'environnement injectées par le CI (jamais commitées), journalisation sans données personnelles, `composer audit` en CI.

### RGPD

Un lead est une donnée personnelle **nécessaire** au CRM demandé. Seul le strict nécessaire est stocké, avec une **durée de conservation de 3 ans** après le dernier contact (recommandation CNIL pour la prospection B2B). Au-delà, une tâche Symfony Scheduler **anonymise** le lead. Le consentement est horodaté, et l'export ou la suppression sur demande se fait depuis l'admin. Aucune donnée personnelle dans Sanity, dans les logs ou dans les outils d'analytics.

---

## 10. Structure des données

### Sanity (contenu éditorial)

i18n **au niveau des champs** (`sanity-plugin-internationalized-array`) pour tout le contenu structuré : un seul document par projet, slug partagé FR/EN, aucune désynchronisation possible (le problème n° 1 de l'audit). Le Journal (phase 2) utilise l'i18n **au niveau du document**, car un article peut n'exister que dans une langue.

| Document                   | Champs principaux                                                                                                                                                                                                                                                                                                                                                                                                                                                                            |
| -------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `siteSettings` (singleton) | nom, baseline i18n, chiffres clés (`15 ans`, `20+`), email, réseaux, SEO par défaut, image OG par défaut                                                                                                                                                                                                                                                                                                                                                                                     |
| `office`                   | nom i18n, pays, adresse, téléphones, fuseau horaire (`Europe/Paris`, `Indian/Antananarivo`), coordonnées GPS                                                                                                                                                                                                                                                                                                                                                                                 |
| `service`                  | ordre, numéro, titre i18n, slug, résumé, description (Portable Text), bénéfices[], livrables[], technologies → `technology`[], projets liés → `project`[], FAQ[], SEO                                                                                                                                                                                                                                                                                                                        |
| `project`                  | titre, slug, client (nom, type : entreprise / institution / ONG / produit interne), `clientDisplayConsent`, période (début, fin), statut (en ligne / archivé), URL, `featured`, ordre, catégories → `category`[], services → `service`[], technologies → `technology`[], résumé i18n, contexte, challenge, solution, contribution, fonctionnalités[], résultats[] {valeur, libellé, source}, couverture, galerie[] {image, type d'appareil, légende i18n, alt i18n}, couleur d'ambiance, SEO |
| `category`                 | titre i18n, slug, est-un-filtre (bool)                                                                                                                                                                                                                                                                                                                                                                                                                                                       |
| `technology`               | nom, slug, logo SVG, couche (`frontend`, `api`, `backend`, `data`, `infrastructure`, `cicd`, `mobile`, `cms-ecommerce`), statut (`stack-actuelle`, `maitrisee`, `historique`), description i18n                                                                                                                                                                                                                                                                                              |
| `teamMember`               | nom, rôle i18n, photo, bio i18n, ordre, affiché (bool)                                                                                                                                                                                                                                                                                                                                                                                                                                       |
| `processStep`              | ordre, titre i18n, description i18n                                                                                                                                                                                                                                                                                                                                                                                                                                                          |
| `testimonial`              | **désactivé** tant qu'aucun témoignage réel n'existe                                                                                                                                                                                                                                                                                                                                                                                                                                         |
| `page`                     | pages éditoriales (À propos, Légal, Confidentialité) composées de sections typées                                                                                                                                                                                                                                                                                                                                                                                                            |
| `seo` (objet)              | title, description, image OG, noindex, canonical                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| `article` (phase 2)        | titre, slug, langue, auteur → `teamMember`, date, tags, corps, SEO                                                                                                                                                                                                                                                                                                                                                                                                                           |

Les règles de validation Sanity imposent par exemple un alt FR + EN pour chaque image, un résumé dans les deux langues avant publication, et une source pour tout résultat chiffré.

### MySQL (backend)

| Table                         | Colonnes clés                                                                                                                                                                                                                                                                                                             |
| ----------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `lead`                        | id (ULID), reference (`GAW-2026-00042`), full_name, email, phone, company, project_type (enum), budget_range (enum, nullable), timeline (enum, nullable), message, locale, source_url, utm_* , consent_at, status (`new`, `qualified`, `proposal`, `won`, `lost`, `archived`), created_at, last_contact_at, anonymized_at |
| `client`                      | id, name, type, country, website, created_at                                                                                                                                                                                                                                                                              |
| `client_contact`              | id, client_id, full_name, email, phone, role                                                                                                                                                                                                                                                                              |
| `opportunity`                 | id, client_id, lead_id, title, stage, estimated_value, currency, expected_close_at                                                                                                                                                                                                                                        |
| `note`                        | id, subject_type, subject_id, author_id, body, created_at                                                                                                                                                                                                                                                                 |
| `admin_user`                  | id, email, password, roles, totp_secret (chiffré), last_login_at                                                                                                                                                                                                                                                          |
| `messenger_messages`          | transport `failed` uniquement (les messages courants passent par Redis)                                                                                                                                                                                                                                                   |
| `doctrine_migration_versions` | —                                                                                                                                                                                                                                                                                                                         |

Index : `lead(status, created_at)`, `lead(email)`, `opportunity(stage)`. Toutes les tables sont créées par les **migrations Doctrine**, exécutées automatiquement en déploiement.

---

## 11. Stratégie SEO

| Domaine                  | Mise en œuvre                                                                                                                                                                                                                                                                                                                                                       |
| ------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **URLs**                 | FR sans préfixe, EN sous `/en/`, **slugs identiques** dans les deux langues (`/projects/magicstay/` ↔ `/en/projects/magicstay/`) comme demandé dans le brief. Barre oblique finale systématique                                                                                                                                                                     |
| **i18n**                 | Routage i18n natif d'Astro (`defaultLocale: 'fr'`, `prefixDefaultLocale: false`). Le sélecteur de langue pointe vers la page équivalente                                                                                                                                                                                                                            |
| **hreflang**             | `fr`, `en`, `x-default` (→ FR) sur chaque page, réciproques, aussi dans le sitemap                                                                                                                                                                                                                                                                                  |
| **Meta**                 | Title (≤ 60 car.) et description (≤ 155) uniques par page et par langue, éditables dans Sanity avec repli automatique                                                                                                                                                                                                                                               |
| **Canonical**            | Auto-référent ; les pages filtrées `/projects/?category=` pointent vers `/projects/`                                                                                                                                                                                                                                                                                |
| **Open Graph / X**       | Image 1200×630 générée au build pour chaque page (Satori + resvg), aux couleurs de la DA, avec titre et métadonnées                                                                                                                                                                                                                                                 |
| **Sitemap**              | `@astrojs/sitemap` avec alternates i18n, `lastmod` issu de Sanity                                                                                                                                                                                                                                                                                                   |
| **robots.txt**           | Autorise tout sauf `/og/` ; référence le sitemap. Préproduction en `noindex` + Cloudflare Access                                                                                                                                                                                                                                                                    |
| **Structure**            | Un H1 par page, Hn hiérarchiques, fil d'Ariane visible sur les pages internes                                                                                                                                                                                                                                                                                       |
| **Schema.org (JSON-LD)** | `Organization` (logo, `sameAs` Facebook/LinkedIn, `address` des 2 bureaux, `contactPoint`), `WebSite`, `WebPage` / `AboutPage` / `ContactPage`, `BreadcrumbList`, `Service` (provider = Organization), `CreativeWork` pour chaque projet (`creator`, `dateCreated`, `keywords` = technos, `url`). Uniquement des données réelles : ni `Review` ni `AggregateRating` |
| **Migration**            | Plan de 301 de l'audit (§13) déployé dans `_redirects` Cloudflare ; vérification post-lancement dans Google Search Console (deux propriétés, FR et EN)                                                                                                                                                                                                              |
| **Contenu**              | Pages services riches (problèmes traités, livrables, technos, projets liés, FAQ) ciblant les intentions « création application mobile Madagascar », « agence développement web Grenoble », « software development agency Madagascar »… ; Journal en phase 2 pour le contenu de fond                                                                                 |
| **Local**                | Recommandé : fiches Google Business Profile pour Claix et Antananarivo, NAP (nom, adresse, téléphone) strictement identique au site                                                                                                                                                                                                                                 |

---

## 12. Stratégie performance

**Budgets (bloquants en CI, Lighthouse CI sur 6 pages types, mobile 4G simulée)**

| Métrique                                             | Cible                                                                      |
| ---------------------------------------------------- | -------------------------------------------------------------------------- |
| Lighthouse Performance / A11y / Best Practices / SEO | ≥ 95 / ≥ 98 / ≥ 95 / 100                                                   |
| LCP                                                  | < 2,0 s                                                                    |
| CLS                                                  | < 0,05                                                                     |
| INP                                                  | < 150 ms                                                                   |
| JS total homepage (gzip)                             | < 90 Ko, dont GSAP + ScrollTrigger + SplitText ≈ 50 Ko, chargés en différé |
| JS pages légales                                     | 0 Ko hors nav                                                              |
| CSS (gzip)                                           | < 30 Ko                                                                    |
| Poids homepage (premier écran)                       | < 500 Ko                                                                   |

**Leviers**

- SSG + CDN Cloudflare (HTML en cache en périphérie, assets immuables hashés avec `max-age=31536000, immutable`).
- **Images** : CDN d'images Sanity (`auto=format` → AVIF/WebP, `srcset` et `sizes` précis, recadrage par hotspot), `width`/`height` toujours renseignés, `loading="lazy"` hors premier écran, `fetchpriority="high"` sur l'image LCP. Les 15 captures existantes sont migrées dans Sanity.
- **Polices** : 3 familles en woff2 variables sous-ensemblées, préchargement d'Archivo seulement, polices de repli à métriques ajustées.
- **JS** : islands minimales, GSAP importé dynamiquement après `requestIdleCallback`, plugins chargés à la demande (SplitText uniquement s'il y a des `data-anim="reveal-lines"`), aucun jQuery, aucun script tiers bloquant.
- **Tiers** : Turnstile chargé uniquement quand le formulaire entre dans le viewport ; analytics Cloudflare Web Analytics (sans cookie, ~5 Ko).
- **HTML/CSS** : CSS critique inline (Astro), Tailwind v4 purgé, compression Brotli et HTTP/3 via Cloudflare.
- **Navigation** : `prefetch` Astro au survol et au viewport pour les liens internes.

---

## 15. Arborescence des pages

| Page             | FR                              | EN                     | Contenu                                                                                                                                                                                                  | Statut                                       |
| ---------------- | ------------------------------- | ---------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------- |
| Accueil          | `/`                             | `/en/`                 | Récit en chapitres (voir ci-dessous)                                                                                                                                                                     | Lancement                                    |
| Services         | `/services/`                    | `/en/services/`        | Index des 6 services, méthode, CTA                                                                                                                                                                       | Lancement                                    |
| Service          | `/services/{slug}/` ×6          | `/en/services/{slug}/` | Problèmes traités, approche, livrables, technos, projets liés, FAQ, CTA                                                                                                                                  | Lancement                                    |
| Réalisations     | `/projects/`                    | `/en/projects/`        | Projets mis en avant + liste filtrable (catégorie, service, techno) + recherche                                                                                                                          | Lancement                                    |
| Étude de cas     | `/projects/{slug}/` ×15         | `/en/projects/{slug}/` | Hero · fiche (client, période, type, liens) · contexte · challenge · solution · contribution · technologies · fonctionnalités · galerie · résultats · projets liés · CTA. **Sections masquées si vides** | Lancement                                    |
| Technologie      | `/technology/`                  | `/en/technology/`      | Diagramme de stack par couches, stack actuelle et technologies maîtrisées, outillage qualité et CI/CD, sécurité                                                                                          | Lancement                                    |
| À propos         | `/about/`                       | `/en/about/`           | Manifeste, chiffres réels, équipe, deux bureaux, méthode, valeurs (performance, confiance)                                                                                                               | Lancement                                    |
| Contact          | `/contact/`                     | `/en/contact/`         | Formulaire multi-étapes, bureaux, téléphones, email, heures locales                                                                                                                                      | Lancement                                    |
| Mentions légales | `/legal/`                       | `/en/legal/`           | Éditeur, hébergeurs, propriété intellectuelle                                                                                                                                                            | Lancement (données légales requises)         |
| Confidentialité  | `/privacy-policy/`              | `/en/privacy-policy/`  | Données collectées, finalités, conservation, droits, sous-traitants                                                                                                                                      | Lancement                                    |
| 404              | `/404`                          | `/en/404`              | Message, liens utiles, recherche de projets                                                                                                                                                              | Lancement                                    |
| Journal          | `/journal/`, `/journal/{slug}/` | `/en/journal/…`        | Articles techniques et retours d'expérience                                                                                                                                                              | Phase 2 (dans la nav à partir de 3 articles) |

**Slugs des services** : `websites`, `mobile-apps`, `web-apps`, `business-software`, `e-commerce`, `maintenance-hosting`.

**Navigation principale** : Services · Réalisations · Technologie · À propos · `[FR|EN]` · **Parler de votre projet** (CTA).

### Homepage : un récit, pas une liste de blocs

| #   | Chapitre                                                                                                                                                                                                                                                                                           | Fond         | Intention                   |
| --- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------ | --------------------------- |
| 00  | **Hero** : « Nous concevons, construisons et faisons évoluer des produits numériques qui tiennent la charge. (dernière partie en cyan) », sous-titre (pour qui), CTA principal et secondaire, **diagramme vivant** reliant Claix et Antananarivo (coordonnées, heure locale), indicateur de scroll | **prussian** | Qui, quoi, pour qui, en 5 s |
| 01  | **Preuves d'abord** : galerie épinglée des 6 projets phares                                                                                                                                                                                                                                        | paper        | Montrer avant de dire       |
| 02  | **Manifeste** : texte qui s'allume mot à mot (performance, confiance), puis chiffres réels                                                                                                                                                                                                         | paper        | Pourquoi nous               |
| 03  | **Capacités** : index interactif des 6 services                                                                                                                                                                                                                                                    | paper        | Que faisons-nous            |
| 04  | **Ingénierie** : le diagramme de stack se construit au scroll                                                                                                                                                                                                                                      | **prussian** | Haut niveau technique       |
| 05  | **Méthode** : Empathie → Expertise → Solution → Développement, boucle de sprint Scrum                                                                                                                                                                                                              | paper-2      | Comment on travaille        |
| 06  | **Pourquoi Genius** : performance, expérience, qualité, communication, expertise technique, collaboration internationale                                                                                                                                                                           | paper        | Rassurer                    |
| 07  | **Deux bureaux, 1 à 2 h de décalage** : France / Madagascar en écran partagé                                                                                                                                                                                                                       | paper        | Humain et international     |
| 08  | **CTA final** : « Construisons quelque chose de solide. » + raccourci vers le formulaire                                                                                                                                                                                                           | **prussian** | Conversion                  |
| —   | Footer                                                                                                                                                                                                                                                                                             | prussian     | Navigation complète         |

---

## 16. Parcours utilisateur et conversion

### Profils cibles

| Profil                    | Besoin                                         | Ce qui le convainc                                                     | Porte d'entrée probable          |
| ------------------------- | ---------------------------------------------- | ---------------------------------------------------------------------- | -------------------------------- |
| Fondateur·rice de startup | MVP, app mobile, vitesse                       | Produits internes réussis (Taxibrousse, Emarket), méthode Scrum        | Recherche, LinkedIn              |
| Dirigeant·e de PME / ETI  | Site, e-commerce, logiciel métier, maintenance | Projets e-commerce, maintenance, deux bureaux, interlocuteur en France | Recherche locale, recommandation |
| Institution / ONG / DSI   | Fiabilité, sécurité, durée                     | DGI, Transparency International, page Technologie, sécurité            | Recommandation, appel d'offres   |
| Client international      | Équipe de delivery offshore fiable             | France + Madagascar, fuseau proche de l'Europe, communication          | Recherche EN, LinkedIn           |

### Entonnoir

```
Découverte  →  Conviction                     →  Décision                  →  Contact          →  Après envoi
(Google,       (études de cas, service lié,      (page Technologie /         (formulaire        (confirmation
 LinkedIn)      méthode)                          À propos, chiffres)         3 étapes)           + email + suivi)
```

**Conversion principale** : formulaire « Parler de votre projet ».

1. **Type de projet** (chips, multi-choix) : Site · App mobile · App web · Logiciel métier · E-commerce · Maintenance · Autre.
2. **Contexte** (facultatif) : budget indicatif, échéance, message.
3. **Coordonnées** : nom, email, téléphone (facultatif), entreprise (facultatif), consentement.

Le formulaire est pré-rempli selon la page d'origine (depuis `/services/mobile-apps/`, la chip « App mobile » est cochée). Écran de succès : référence de la demande, prochaines étapes, délai de réponse indicatif (**délai à définir avec vous**), liens vers trois études de cas.

**Conversions secondaires** : email et téléphone cliquables par bureau, consultation d'une étude de cas, clic « Voir le site » d'un projet.

**Placement des CTA** : nav (permanent) · fin du hero · fin de chaque chapitre clé (mini-CTA contextuel) · fin de chaque étude de cas (« Un projet similaire ? ») · fin de chaque page service · footer. Un seul CTA principal par écran.

**Mesure** : Cloudflare Web Analytics (sans cookie, pas de bandeau requis) pour l'audience ; événements de conversion comptés côté backend (leads par source et par page d'origine, visibles dans l'admin). En option, Plausible (hébergé dans l'UE) pour les événements front sans cookie.

---

## 17. Dépendances et bibliothèques

### Frontend (`apps/web`)

| Paquet                                                                                                                                                                           | Rôle                                                    |
| -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------- |
| `astro` 7.x, `@astrojs/react`, `@astrojs/sitemap`, `@astrojs/check`                                                                                                              | Framework, islands, sitemap i18n, vérification de types |
| `react` 19, `react-dom`                                                                                                                                                          | Islands                                                 |
| `typescript` (strict)                                                                                                                                                            | —                                                       |
| `tailwindcss` 4, `@tailwindcss/vite`                                                                                                                                             | Styles, tokens `@theme`                                 |
| `gsap` 3.13+ (ScrollTrigger, SplitText ; tous les plugins sont gratuits depuis 2025)                                                                                             | Animation                                               |
| `@sanity/client`, `@sanity/image-url`, `astro-portabletext`                                                                                                                      | Contenu                                                 |
| `zod`                                                                                                                                                                            | Validation du formulaire (schéma partagé)               |
| `satori`, `@resvg/resvg-js`                                                                                                                                                      | Images OG au build                                      |
| `@fontsource-variable/archivo` (axes wght + wdth), `@fontsource-variable/martian-mono`                                                                                           | Polices auto-hébergées                                  |
| **Dev** : `eslint` (flat config, `eslint-plugin-astro`, `jsx-a11y`), `prettier` (+ plugins astro et tailwind), `vitest`, `@playwright/test`, `@axe-core/playwright`, `@lhci/cli` | Qualité, tests, accessibilité, performance              |

**Volontairement écartés** : Lenis et tout smooth-scroll (détournement du scroll natif : accessibilité, INP), Three.js/WebGL (coût élevé pour un gain faible ici), Framer Motion (doublon de GSAP), jQuery, bibliothèques de composants UI (la DA est sur mesure), `react-hook-form` (un seul formulaire, Zod suffit).

### CMS (`cms/sanity`)

`sanity` (dernière majeure), `sanity-plugin-internationalized-array`, `@sanity/document-internationalization` (Journal), `@sanity/vision`, `sanity typegen` → `packages/types`.

### Backend (`apps/api`)

| Paquet                                                                                                                                                                                                          | Rôle                                              |
| --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------- |
| `symfony/*` 7.4 LTS (framework-bundle, validator, messenger, mailer, rate-limiter, scheduler, uid, security-bundle, monolog, twig)                                                                              | Cœur                                              |
| `api-platform/symfony` 4.x                                                                                                                                                                                      | API REST, OpenAPI                                 |
| `doctrine/orm` 3, `doctrine/doctrine-migrations-bundle`                                                                                                                                                         | Persistance, migrations                           |
| `easycorp/easyadmin-bundle` 4                                                                                                                                                                                   | Back-office CRM                                   |
| `scheb/2fa-bundle` + `scheb/2fa-totp`                                                                                                                                                                           | 2FA admin                                         |
| `nelmio/cors-bundle`                                                                                                                                                                                            | CORS strict                                       |
| `symfony/brevo-mailer` (ou Scaleway TEM / Postmark)                                                                                                                                                             | Transport email (Brevo : français, conforme RGPD) |
| `runtime/frankenphp-symfony`                                                                                                                                                                                    | Mode worker                                       |
| **Dev** : `phpunit/phpunit` 12, `zenstruck/foundry`, `dama/doctrine-test-bundle`, `phpstan/phpstan` (niveau max) + extensions symfony/doctrine, `friendsofphp/php-cs-fixer`, `rector/rector`, `deptrac/deptrac` | Tests, analyse statique, respect des couches      |

**Écartés** : `lexik/jwt-authentication-bundle` et OAuth (aucun besoin actuel, voir §9).

### Infrastructure

| Outil                                                                                                                                                              | Rôle                                  |
| ------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ------------------------------------- |
| Docker + Compose ; images `dunglas/frankenphp` (PHP 8.4), `mysql:8.4`, `redis:7.4` (ou `valkey/valkey:8`, compatible et sous licence BSD), `axllent/mailpit` (dev) | Stack locale et production            |
| Cloudflare : Workers Static Assets (site), Tunnel (API sans port ouvert), WAF, Turnstile, Access (admin et préproduction), Web Analytics                           | Edge                                  |
| VPS dans l'UE (OVHcloud, Scaleway ou Hetzner)                                                                                                                      | Hébergement de l'API (proximité RGPD) |
| GitHub Actions ; `wrangler` ; Trivy (scan des images) ; gitleaks (secrets) ; Renovate (mises à jour) ; `composer audit` / `pnpm audit`                             | CI/CD et sécurité                     |

### Pipeline (rappel)

`push/PR` → install (cache) → lint (ESLint, Prettier, PHP-CS-Fixer) → analyse statique (astro check, tsc, PHPStan, Deptrac) → tests unitaires → tests d'intégration (MySQL + Redis en services) → build web + Studio → build des images Docker → sécurité (Trivy, gitleaks, audits) → Lighthouse CI + axe → **déploiement** : web sur Cloudflare ; API par SSH via Tunnel : `pull` → `doctrine:database:create --if-not-exists` → `doctrine:migrations:migrate --no-interaction --allow-no-migration` → `cache:clear` / `cache:warmup` → redémarrage des workers → healthcheck `/api/v1/health` → rollback automatique en cas d'échec. Aucune commande destructive en production ; environnement GitHub `production` avec approbation manuelle.

---

## Décisions validées (19 octobre 2026)

1. **Direction artistique A « Cyanotype »** : validée explicitement.
2. Les points suivants sont adoptés selon la recommandation, faute de contre-indication. Ils restent modifiables :
   - logo : monogramme GAW conservé et redessiné en vectoriel (wordmark typographique provisoire en attendant) ;
   - site 100 % statique + API sur `api.` + admin sur `admin.` ;
   - Symfony 7.4 LTS ;
   - slugs anglais identiques en FR et EN ;
   - CRM dans Symfony + EasyAdmin, leads anonymisés après 3 ans ;
   - Turnstile (anti-spam) et Brevo (email) ;
   - API sur un VPS dans l'UE (fournisseur à choisir) ;
   - Journal en phase 2.
