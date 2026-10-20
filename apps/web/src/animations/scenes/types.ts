import type { PresetEnv } from '../presets/types';

/**
 * Une scène orchestre une animation complexe propre à une section (hero, galerie épinglée,
 * diagramme d'ingénierie). Même contrat qu'un preset : synchrone, exécutée dans le matchMedia.
 */
export type Scene = (root: HTMLElement, env: PresetEnv) => (() => void) | undefined;
