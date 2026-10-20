// @ts-check
import { defineConfig } from 'astro/config';
import react from '@astrojs/react';
import sitemap from '@astrojs/sitemap';
import tailwindcss from '@tailwindcss/vite';
import { readFileSync } from 'node:fs';
import { rename, rmdir } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import { parseEnv } from 'node:util';

const SITE_URL = process.env.PUBLIC_SITE_URL ?? 'https://genius-at-work.com';

/** URL de l'API, lue comme import.meta.env côté pages : variable du pipeline, sinon .env local. */
const readApiUrl = () => {
  if (process.env.PUBLIC_API_URL) return process.env.PUBLIC_API_URL;
  try {
    return parseEnv(readFileSync(new URL('.env', import.meta.url), 'utf8')).PUBLIC_API_URL;
  } catch {
    return undefined;
  }
};
const API_URL = new URL(readApiUrl() ?? 'http://127.0.0.1:8088');
const API_ORIGIN = API_URL.origin;
const TURNSTILE_ORIGIN = 'https://challenges.cloudflare.com';

/**
 * Routes exclues du sitemap : styleguide interne, et pages juridiques (noindex tant que
 * les mentions obligatoires ne sont pas complètes, et sans intérêt pour la recherche).
 */
const INTERNAL_ROUTES = ['/design-system/', '/legal/', '/privacy-policy/', '/404/'];
const NOT_PAGES = ['/og/', '/robots.txt'];

/**
 * Cloudflare sert le 404.html le plus proche de l'URL demandée. Astro ne produit 404.html
 * qu'à la racine : la version anglaise (en/404/index.html) est renommée en en/404.html.
 * @type {import('astro').AstroIntegration}
 */
const localizedNotFound = {
  name: 'gaw:localized-404',
  hooks: {
    'astro:build:done': async ({ dir }) => {
      const folder = new URL('en/404/', dir);
      await rename(new URL('index.html', folder), new URL('en/404.html', dir));
      await rmdir(folder);
    },
  },
};

/**
 * Développement : le contenu vient du back-office (API). Toutes les 2 s, compare la version du
 * contenu ; si elle change, recharge le module qui le lit (et ses dépendants) puis la page.
 * @type {import('astro').AstroIntegration}
 */
const contentLiveReload = {
  name: 'gaw:content-live-reload',
  hooks: {
    'astro:server:setup': ({ server, logger }) => {
      const loader = fileURLToPath(new URL('src/lib/content-api/load.ts', import.meta.url));
      const endpoint = new URL('/api/v1/site-content', API_ORIGIN);
      /** @type {string | null | undefined} */
      let version;
      const timer = setInterval(async () => {
        try {
          const { version: current } = await (await fetch(endpoint)).json();
          if (version !== undefined && current !== version) {
            for (const environment of Object.values(server.environments)) {
              const module = environment.moduleGraph.getModuleById(loader);
              if (module) await environment.reloadModule(module);
            }
            server.ws.send({ type: 'full-reload' });
            logger.info('Contenu modifié dans le back-office : page rechargée.');
          }
          version = current;
        } catch {
          // API arrêtée : la prochaine requête de page affichera l'erreur.
        }
      }, 2000);
      server.httpServer?.on('close', () => clearInterval(timer));
    },
  },
};

export default defineConfig({
  site: SITE_URL,
  output: 'static',
  trailingSlash: 'always',
  build: {
    format: 'directory',
    inlineStylesheets: 'auto',
  },
  i18n: {
    defaultLocale: 'fr',
    locales: ['fr', 'en'],
    routing: {
      prefixDefaultLocale: false,
    },
  },
  /**
   * CSP en <meta> : Astro ajoute l'empreinte de chaque script et style inline de la page.
   * frame-ancestors (ignoré en <meta>) reste dans public/_headers.
   * style-src-attr : attributs style (largeurs calculées, iframe Turnstile), sans risque d'exécution.
   */
  security: {
    csp: {
      directives: [
        "default-src 'self'",
        "img-src 'self' data:",
        "font-src 'self'",
        `connect-src 'self' ${API_ORIGIN}`,
        `frame-src ${TURNSTILE_ORIGIN}`,
        "base-uri 'self'",
        "object-src 'none'",
        "form-action 'self'",
      ],
      scriptDirective: {
        resources: ["'self'", TURNSTILE_ORIGIN],
      },
      styleDirective: {
        resources: ["'self'", { resource: "'unsafe-inline'", kind: 'attribute' }],
      },
    },
  },

  // Images du back-office (servies par l'API sous /media) : téléchargées et optimisées au build.
  image: {
    remotePatterns: [
      {
        protocol: API_URL.protocol.slice(0, -1),
        hostname: API_URL.hostname,
        port: API_URL.port,
        pathname: '/media/**',
      },
    ],
  },

  // Pas de Markdown sur le site : Shiki (styles inline, incompatibles avec la CSP) est inutile.
  markdown: { syntaxHighlight: false },
  prefetch: {
    prefetchAll: false,
    defaultStrategy: 'hover',
  },
  integrations: [
    react(),
    sitemap({
      i18n: {
        defaultLocale: 'fr',
        locales: { fr: 'fr-FR', en: 'en' },
      },
      filter: (page) =>
        !INTERNAL_ROUTES.some((route) => page.endsWith(route)) &&
        !NOT_PAGES.some((route) => new URL(page).pathname.startsWith(route)),
    }),
    localizedNotFound,
    contentLiveReload,
  ],
  vite: {
    plugins: [tailwindcss()],
    // Aucune ressource inlinée en data: (polices, images) : font-src et img-src restent 'self'.
    build: { assetsInlineLimit: 0 },
  },
});
