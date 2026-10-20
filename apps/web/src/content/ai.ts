import type { ServiceSlug } from '@/types/content';
import { PAGES, perLocale } from './texts';

/** Offre IA (back-office : Pages et textes › IA & automatisation, et Bloc IA de l'accueil). */

export const AI_SERVICE: ServiceSlug = 'ai';

export const AI_PATH = '/services/ai/';

export const AI_PAGE = perLocale((locale) => PAGES['ai-page'][locale]);

export const AI_SECTION = perLocale((locale) => PAGES['ai-section'][locale]);
