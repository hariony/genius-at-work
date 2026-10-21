import { readFile } from 'node:fs/promises';
import { z } from 'zod';
import type { Locale } from '@/lib/i18n/config';
import type { PageId, PageTexts } from '@/types/pages';

/**
 * Contenu éditorial géré dans le back-office, lu sur l'API (`GET /api/v1/site-content`).
 * - En local et en production : l'API (CONTENT_API_URL, sinon PUBLIC_API_URL), une fois par build.
 * - En CI, sans API : l'instantané CONTENT_SNAPSHOT (apps/api/data/site-content.snapshot.json),
 *   tenu à jour par les tests de l'API.
 * Si aucune source ne répond, le build échoue : jamais de site publié sans son contenu.
 * Les images (/media/…) sont toujours téléchargées sur PUBLIC_API_URL (en CI : apps/api/data servi
 * localement) et optimisées par Astro.
 */

const text = z.string().trim().min(1);
const localized = z.object({ fr: text, en: text });

const settings = z.object({
  name: text,
  email: z.email(),
  socials: z.array(z.object({ id: text, label: text, href: z.url() })),
  offices: z
    .array(
      z.object({
        id: text,
        name: localized,
        city: text,
        addressLines: z.array(text).min(1),
        phones: z.array(z.object({ display: text, e164: z.string().regex(/^\+\d{6,15}$/) })),
        timeZone: text,
        utcLabel: text,
        coordinates: z.object({ lat: z.number(), lng: z.number() }),
      }),
    )
    .min(1),
});

const projectCategory = z.enum([
  'web-app',
  'mobile',
  'ux-ui',
  'e-commerce',
  'innovation',
  'showcase',
  'travel',
  'ai',
]);
const year = z.number().int().min(1990).max(2100);

/** Image du back-office : chemin /media/… sur l'API, avec ses dimensions (optimisée au build). */
const image = z.object({
  src: z.string().startsWith('/media/'),
  width: z.number().int().positive(),
  height: z.number().int().positive(),
});

const project = z.object({
  slug: z.string().regex(/^[a-z0-9]+(?:-[a-z0-9]+)*$/),
  title: text,
  client: text.nullable().transform((value) => value ?? undefined),
  clientType: z
    .enum(['company', 'institution', 'ngo', 'in-house'])
    .nullable()
    .transform((value) => value ?? undefined),
  period: z
    .object({ start: year, end: year })
    .nullable()
    .transform((value) => value ?? undefined),
  categories: z.array(projectCategory).min(1),
  technologies: z.array(text),
  url: z
    .url()
    .nullable()
    .transform((value) => value ?? undefined),
  summary: localized,
  cover: image,
  coverAlt: localized,
  featured: z.boolean(),
  tint: z
    .string()
    .regex(/^#[0-9a-f]{6}$/)
    .nullish()
    .transform((value) => value ?? undefined),
});

const service = z.object({
  slug: z.enum([
    'websites',
    'mobile-apps',
    'web-apps',
    'business-software',
    'ai',
    'e-commerce',
    'maintenance-hosting',
  ]),
  title: localized,
  summary: localized,
  intro: localized,
  scope: z.object({ fr: z.array(text).min(1), en: z.array(text).min(1) }),
  technologies: z.array(text),
  relatedProjects: z.array(text),
});

/**
 * Textes des pages : forme validée par l'API (catalogue des pages) à chaque enregistrement ;
 * ici, seule leur présence est vérifiée.
 */
const PAGE_IDS = [
  'home',
  'about',
  'services-page',
  'ai-page',
  'projects-page',
  'technology-page',
  'contact',
  'not-found',
  'ai-section',
  'method',
  'technologies',
  'contact-form',
  'interface',
  'legal',
  'privacy',
] as const satisfies readonly PageId[];

const pageTexts = z.object({
  fr: z.record(z.string(), z.unknown()),
  en: z.record(z.string(), z.unknown()),
});

const pages = z
  .object(Object.fromEntries(PAGE_IDS.map((id) => [id, pageTexts.nullable()])))
  .transform((value, ctx) => {
    const missing = PAGE_IDS.filter((id) => !value[id]);
    if (missing.length === 0) return value as unknown as Pages;
    ctx.addIssue({
      code: 'custom',
      message: `textes absents (${missing.join(', ')}) : importez le contenu initial (bin/console app:content:import)`,
    });
    return z.NEVER;
  });

export type Pages = { [K in PageId]: Record<Locale, PageTexts[K]> };

const siteContent = z.object({
  pages,
  projects: z.array(project),
  services: z.array(service),
  settings: settings.nullable().transform((value, ctx) => {
    if (value) return value;
    ctx.addIssue({
      code: 'custom',
      message:
        'réglages absents : importez le contenu initial (bin/console app:content:import) ou remplissez-les dans le back-office',
    });
    return z.NEVER;
  }),
});

export type SiteContent = z.output<typeof siteContent>;

type Env = Partial<Record<'CONTENT_API_URL' | 'CONTENT_SNAPSHOT' | 'PUBLIC_API_URL', string>>;

function env(): Env {
  // import.meta.env est absent hors de Vite (scripts) : process.env seul.
  return { ...process.env, ...(import.meta.env ?? {}) };
}

async function fetchRaw(): Promise<{ source: string; data: unknown }> {
  const { CONTENT_SNAPSHOT, CONTENT_API_URL, PUBLIC_API_URL } = env();
  if (CONTENT_SNAPSHOT) {
    return { source: CONTENT_SNAPSHOT, data: JSON.parse(await readFile(CONTENT_SNAPSHOT, 'utf8')) };
  }
  const base = CONTENT_API_URL ?? PUBLIC_API_URL;
  if (!base) throw new Error('Contenu : définissez PUBLIC_API_URL (ou CONTENT_SNAPSHOT).');
  const url = new URL('/api/v1/site-content', base).href;
  let response: Response;
  try {
    response = await fetch(url, { headers: { Accept: 'application/json' } });
  } catch (cause) {
    throw new Error(
      `Contenu : l'API ne répond pas (${url}). Lancez-la avec « docker compose up -d ».`,
      {
        cause,
      },
    );
  }
  if (!response.ok) throw new Error(`Contenu : ${url} a répondu ${response.status}.`);
  return { source: url, data: await response.json() };
}

/** Les images sont servies par l'API : chemins rendus absolus pour l'optimisation au build. */
function mediaOrigin(): string {
  const { CONTENT_API_URL, PUBLIC_API_URL } = env();
  const base = CONTENT_API_URL ?? PUBLIC_API_URL;
  if (!base) throw new Error('Contenu : définissez PUBLIC_API_URL (images servies par l’API).');
  return new URL(base).origin;
}

export async function fetchSiteContent(): Promise<SiteContent> {
  const { source, data } = await fetchRaw();
  const result = siteContent.safeParse(data);
  if (!result.success) {
    throw new Error(`Contenu invalide (${source}) :\n${z.prettifyError(result.error)}`);
  }
  const origin = mediaOrigin();
  return {
    ...result.data,
    projects: result.data.projects.map((item) => ({
      ...item,
      cover: { ...item.cover, src: new URL(item.cover.src, origin).href },
    })),
  };
}

let pending: Promise<SiteContent> | undefined;

/** Chargé une seule fois par build (et par rechargement du contenu en développement). */
export const loadSiteContent = () => (pending ??= fetchSiteContent());
