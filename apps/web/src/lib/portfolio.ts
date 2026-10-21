import { PAGES } from '@/content/texts';
import type { Locale } from '@/lib/i18n';
import type { Project, ProjectCategory } from '@/types/content';

/** Nom d'une catégorie de projet (back-office : Pages et textes › Réalisations). */
export function categoryLabel(category: ProjectCategory, locale: Locale): string {
  return PAGES['projects-page'][locale].categoryLabels[category];
}

/** « 2019 » ou « 2019–2022 » ; undefined si la période n'est pas renseignée. */
export function projectPeriod(project: Project): string | undefined {
  if (!project.period) return undefined;
  const { start, end } = project.period;
  return start === end ? `${start}` : `${start}–${end}`;
}
