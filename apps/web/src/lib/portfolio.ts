import type { Project, ProjectCategory } from '@/types/content';

/** Libellés des catégories de projets (mêmes libellés dans le back-office : AdminTwigExtension). */
export const CATEGORY_LABELS: Record<ProjectCategory, { fr: string; en: string }> = {
  'web-app': { fr: 'App web', en: 'Web app' },
  mobile: { fr: 'Mobile', en: 'Mobile' },
  'ux-ui': { fr: 'Design UX/UI', en: 'UX/UI design' },
  'e-commerce': { fr: 'E-commerce', en: 'E-commerce' },
  innovation: { fr: 'Innovation', en: 'Innovation' },
  showcase: { fr: 'Site vitrine', en: 'Showcase site' },
  travel: { fr: 'Voyage', en: 'Travel' },
  ai: { fr: 'IA', en: 'AI' },
};

/** « 2019 » ou « 2019–2022 » ; undefined si la période n'est pas renseignée. */
export function projectPeriod(project: Project): string | undefined {
  if (!project.period) return undefined;
  const { start, end } = project.period;
  return start === end ? `${start}` : `${start}–${end}`;
}
