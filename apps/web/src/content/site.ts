import { loadSiteContent } from '@/lib/content-api/load';

/**
 * Réglages du site (nom, e-mail, réseaux sociaux, bureaux), gérés dans le back-office :
 * /admin/content/settings. Lus sur l'API au build (src/lib/content-api).
 */

const { settings } = await loadSiteContent();

export type SiteSettings = typeof settings;
export type Office = SiteSettings['offices'][number];

export const SITE = {
  ...settings,
  /** Logotype : monogramme « G » + « genius@work » (components/ui/Wordmark.astro). */
  wordmark: 'genius@work',
};

/** « 45.12°N 5.67°E » */
export function formatCoordinates({ lat, lng }: Office['coordinates']): string {
  const part = (value: number, positive: string, negative: string) =>
    `${Math.abs(value).toFixed(2)}°${value >= 0 ? positive : negative}`;
  return `${part(lat, 'N', 'S')} ${part(lng, 'E', 'W')}`;
}
