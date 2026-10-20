import { DEFAULT_LOCALE, LOCALES, type Locale } from './config';

/**
 * Les slugs sont identiques en FR et EN (décision validée) :
 * une page FR `/projects/magicstay/` a pour équivalent EN `/en/projects/magicstay/`.
 * Changer de langue revient donc à ajouter ou retirer le préfixe de locale.
 */

const ensureTrailingSlash = (path: string): string => (path.endsWith('/') ? path : `${path}/`);

/** Chemin sans préfixe de locale : `/en/projects/` → `/projects/`. */
export function stripLocale(pathname: string): string {
  for (const locale of LOCALES) {
    if (locale === DEFAULT_LOCALE) continue;
    const prefix = `/${locale}`;
    if (pathname === prefix || pathname === `${prefix}/`) return '/';
    if (pathname.startsWith(`${prefix}/`)) return pathname.slice(prefix.length);
  }
  return pathname;
}

/** Construit l'URL relative d'un chemin neutre pour une locale : (`/projects/`, 'en') → `/en/projects/`. */
export function localizePath(path: string, locale: Locale): string {
  const neutral = ensureTrailingSlash(path.startsWith('/') ? path : `/${path}`);
  return locale === DEFAULT_LOCALE ? neutral : `/${locale}${neutral}`;
}

/** Équivalent de la page courante dans une autre langue. */
export function switchLocalePath(pathname: string, target: Locale): string {
  return localizePath(stripLocale(pathname), target);
}

/** Toutes les alternatives d'une page, pour les balises hreflang. */
export function alternates(pathname: string, site: URL): { locale: Locale; href: string }[] {
  const neutral = stripLocale(pathname);
  return LOCALES.map((locale) => ({
    locale,
    href: new URL(localizePath(neutral, locale), site).href,
  }));
}
