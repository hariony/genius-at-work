import { PAGES, perLocale } from './texts';

/** Page 404, servie par Cloudflare pour toute URL inconnue (404.html le plus proche). */
export const NOT_FOUND_PAGE = perLocale((locale) => PAGES['not-found'][locale]);
