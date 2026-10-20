import type { StackGroup } from '@/types/content';
import { PAGES } from './texts';

/** Stack par couches (back-office : Pages et textes › Stack technique). */

const { fr, en } = PAGES.technologies;

export const STACK: StackGroup[] = fr.stack.map((group, i) => ({
  layer: group.layer,
  title: { fr: group.title, en: en.stack[i]?.title ?? group.title },
  role: { fr: group.role, en: en.stack[i]?.role ?? group.role },
  technologies: group.technologies,
}));
