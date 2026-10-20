import type { ProcessStep, Reason } from '@/types/content';
import { PAGES } from './texts';

/** Étapes de la méthode et atouts (back-office : Pages et textes › Méthode et atouts). */

const { fr, en } = PAGES.method;

const bilingual = (items: typeof fr.steps, translations: typeof en.steps) =>
  items.map((item, i) => ({
    title: { fr: item.title, en: translations[i]?.title ?? item.title },
    description: { fr: item.description, en: translations[i]?.description ?? item.description },
  }));

export const PROCESS: ProcessStep[] = bilingual(fr.steps, en.steps);

export const REASONS: Reason[] = bilingual(fr.reasons, en.reasons);
