import { PAGES, perLocale } from './texts';

/** Textes de la page d'accueil (back-office : Pages et textes › Accueil). */
export const HOME_HERO = perLocale((locale) => PAGES.home[locale].hero);

/** En-têtes de chapitres et textes courts des sections de l'accueil. */
export const HOME_SECTIONS = perLocale((locale) => PAGES.home[locale].sections);
