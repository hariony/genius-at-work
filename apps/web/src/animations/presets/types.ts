import type { MotionKit, SplitText } from '../core/gsap';

export interface PresetEnv extends MotionKit {
  /** Chargé par le contrôleur uniquement si la page contient des [data-anim="reveal-lines"]. */
  SplitText: SplitText | undefined;
  /** Pointeur fin (souris) : active les interactions de type magnétisme. */
  finePointer: boolean;
  /** Écran ≥ 1024 px. */
  desktop: boolean;
}

/**
 * Un preset reçoit un élément portant `data-anim="<nom>"` et crée ses tweens de façon synchrone.
 * Il est exécuté dans un gsap.matchMedia() : tout ce qu'il crée est annulé automatiquement
 * à la navigation ou si l'utilisateur active la réduction des animations.
 * Il peut retourner une fonction de nettoyage pour ses écouteurs DOM.
 */
export type Preset = (el: HTMLElement, env: PresetEnv) => (() => void) | undefined;
