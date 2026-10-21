import { getProjects } from '@/services/content';
import type { Project } from '@/types/content';

/**
 * Aplats de marque pour la scène des projets. La couleur dépend de la position du projet
 * dans le portfolio : stable d'une page à l'autre, et deux projets voisins diffèrent.
 * Classes écrites en entier pour que Tailwind les génère.
 */
const TINTS = ['bg-blue', 'bg-lime', 'bg-navy', 'bg-blue-soft'] as const;

export function projectTint(project: Project): (typeof TINTS)[number] {
  const index = Math.max(0, getProjects().indexOf(project));
  return TINTS[index % TINTS.length] ?? 'bg-blue';
}

/** Repères d'une liste (pastilles, traits), avec de l'encre dessus : citron, la signature. */
const MARKERS = ['bg-lime'] as const;

export const markerTint = (index: number): (typeof MARKERS)[number] =>
  MARKERS[index % MARKERS.length] ?? 'bg-lime';
