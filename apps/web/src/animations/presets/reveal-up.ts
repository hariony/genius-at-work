import { markDone, readNumber } from '../core/motion';
import type { Preset } from './types';

/**
 * Bloc qui monte légèrement en apparaissant.
 * Attributs : data-anim-delay, data-anim-trigger="load" (au chargement, pour le haut de page).
 */
const revealUp: Preset = (el, { gsap }) => {
  gsap.fromTo(
    el,
    { autoAlpha: 0, y: 16 },
    {
      autoAlpha: 1,
      y: 0,
      duration: 0.6,
      delay: readNumber(el, 'animDelay', 0),
      clearProps: 'transform',
      ...(el.dataset.animTrigger === 'load'
        ? {}
        : { scrollTrigger: { trigger: el, start: 'top 90%', once: true } }),
    },
  );
  markDone(el);
};

export default revealUp;
