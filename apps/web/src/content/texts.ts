import { loadSiteContent } from '@/lib/content-api/load';
import type { Locale } from '@/lib/i18n/config';

/**
 * Textes des pages gérés dans le back-office (/admin/content/pages), lus sur l'API au build.
 * Les modules de src/content les exposent sous la forme attendue par les composants.
 */
export const { pages: PAGES } = await loadSiteContent();

/** Même valeur prise dans chaque langue. */
export const perLocale = <T>(pick: (locale: Locale) => T): Record<Locale, T> => ({
  fr: pick('fr'),
  en: pick('en'),
});

/** Remplace {clé} par sa valeur ; un emplacement inconnu reste tel quel. */
export const fill = (text: string, values: Record<string, string | number>): string =>
  text.replace(/\{(\w+)\}/g, (match, key: string) => (key in values ? String(values[key]) : match));

/** « {n} projet » / « {n} projets » → fonction du nombre. */
export const plural = (one: string, many: string) => (n: number) => fill(n > 1 ? many : one, { n });
