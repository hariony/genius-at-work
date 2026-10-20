import { AI_PAGE, AI_PATH, AI_SERVICE } from '@/content/ai';
import { CONTACT_PAGE } from '@/content/contact';
import { HOME_HERO } from '@/content/home';
import { CATEGORY_LABELS } from '@/lib/portfolio';
import { ABOUT_PAGE, PROJECTS_PAGE, SERVICES_PAGE, TECHNOLOGY_PAGE } from '@/content/pages';
import { LOCALES, localizePath, useTranslations, type Locale } from '@/lib/i18n';
import { getProjects, getServices } from '@/services/content';

/**
 * Cartes Open Graph générées au build (src/pages/og/[...card].png.ts), une par page indexable.
 * Elles ne reprennent que des textes déjà publiés sur la page correspondante.
 */
export interface OgCard {
  /** Chemin de la page, ex. « /en/projects/magicstay/ ». */
  path: string;
  locale: Locale;
  eyebrow: string;
  title: string;
  /** Ligne de bas de carte (catégories d'un projet, par exemple). */
  meta?: string | undefined;
}

function cardsFor(locale: Locale): OgCard[] {
  const t = useTranslations(locale);
  const page = (path: string, eyebrow: string, title: string, meta?: string): OgCard => ({
    path: localizePath(path, locale),
    locale,
    eyebrow,
    title,
    meta,
  });
  const home = HOME_HERO[locale];

  return [
    page('/', home.eyebrow, `${home.title} ${home.titleHighlight}`),
    page('/services/', t('nav.services'), SERVICES_PAGE[locale].hero.title),
    page('/projects/', t('nav.projects'), PROJECTS_PAGE[locale].hero.title),
    page('/technology/', t('nav.technology'), TECHNOLOGY_PAGE[locale].hero.title),
    page('/about/', t('nav.about'), ABOUT_PAGE[locale].hero.title),
    page('/contact/', t('nav.contact'), CONTACT_PAGE[locale].title),
    ...getServices().map((service) =>
      service.slug === AI_SERVICE
        ? page(AI_PATH, AI_PAGE[locale].hero.eyebrow, AI_PAGE[locale].hero.title)
        : page(`/services/${service.slug}/`, t('nav.services'), service.title[locale]),
    ),
    ...getProjects().map((project) =>
      page(
        `/projects/${project.slug}/`,
        t('nav.projects'),
        project.title,
        project.categories.map((category) => CATEGORY_LABELS[category][locale]).join(' · '),
      ),
    ),
  ];
}

export const OG_CARDS: OgCard[] = LOCALES.flatMap(cardsFor);

/** « / » → « index », « /en/projects/magicstay/ » → « en/projects/magicstay ». */
export const ogCardKey = (path: string): string => path.replace(/^\/|\/$/g, '') || 'index';

/** URL de la carte d'une page, ou undefined si la page n'en a pas (pages non indexées). */
export function ogImageFor(pathname: string): string | undefined {
  const path = pathname.endsWith('/') ? pathname : `${pathname}/`;
  return OG_CARDS.some((card) => card.path === path) ? `/og/${ogCardKey(path)}.png` : undefined;
}
