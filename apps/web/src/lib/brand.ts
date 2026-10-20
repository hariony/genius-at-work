import { getProjects } from '@/services/content';
import type { Project } from '@/types/content';

/**
 * Aplats de marque pour la scène des projets. La couleur dépend de la position du projet
 * dans le portfolio : stable d'une page à l'autre, et deux projets voisins diffèrent.
 * Classes écrites en entier pour que Tailwind les génère.
 */
const TINTS = ['bg-cyan', 'bg-lilac', 'bg-lime', 'bg-blue-soft'] as const;

export function projectTint(project: Project): (typeof TINTS)[number] {
  const index = Math.max(0, getProjects().indexOf(project));
  return TINTS[index % TINTS.length] ?? 'bg-cyan';
}

/** Aplats vifs en alternance pour les repères d'une liste (pastilles, traits), avec encre dessus. */
const MARKERS = ['bg-cyan', 'bg-lilac', 'bg-lime'] as const;

export const markerTint = (index: number): (typeof MARKERS)[number] =>
  MARKERS[index % MARKERS.length] ?? 'bg-cyan';
