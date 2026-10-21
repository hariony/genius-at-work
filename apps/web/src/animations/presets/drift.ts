import { markDone, readNumber } from '../core/motion';
import type { Preset } from './types';

/**
 * Glissement horizontal lié au défilement (bandeau de technologies) : l'élément avance vers la
 * gauche pendant que la section traverse l'écran, puis s'arrête avec elle. Rien ne bouge seul.
 * Attribut : data-anim-distance (pourcentage de la largeur de l'élément, 30 par défaut).
 */
const drift: Preset = (el, { gsap }) => {
  markDone(el);
  const distance = readNumber(el, 'animDistance', 30);
  gsap.fromTo(
    el,
    { xPercent: 0 },
    {
      xPercent: -distance,
      ease: 'none',
      scrollTrigger: { trigger: el, start: 'top bottom', end: 'bottom top', scrub: 0.4 },
    },
  );
};

export default drift;
