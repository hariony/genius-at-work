import { mountAnimations } from './controller';
import { loadMotionKit } from './core/gsap';
import { LAYOUT_CHANGE_EVENT, revealAll } from './core/motion';

/**
 * Monte les animations de la page. Chaque navigation recharge le document (View Transitions
 * natives entre documents) : aucun démontage n'est nécessaire.
 * Le chargement de GSAP est différé au moment où le navigateur est disponible.
 * Un composant qui modifie la mise en page (filtre, accordéon) émet LAYOUT_CHANGE_EVENT
 * sur document pour recalculer les positions des déclencheurs.
 */

declare global {
  interface Window {
    __gawMotionReady?: boolean;
  }
}

const whenIdle = (fn: () => void) =>
  'requestIdleCallback' in window
    ? window.requestIdleCallback(fn, { timeout: 400 })
    : setTimeout(fn, 1);

export function startMotion(): void {
  whenIdle(() => {
    mountAnimations()
      .then(() => {
        window.__gawMotionReady = true;
      })
      .catch((error: unknown) => {
        console.error('[motion] initialisation impossible, contenu affiché sans animation', error);
        document.documentElement.classList.add('motion-off');
        revealAll();
      });
  });

  document.addEventListener(LAYOUT_CHANGE_EVENT, () => {
    if (!window.__gawMotionReady) return;
    void loadMotionKit().then(({ ScrollTrigger }) => ScrollTrigger.refresh());
  });
}
