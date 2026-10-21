import { markDone } from '../core/motion';
import type { Preset } from './types';

/** Filet qui se trace de gauche à droite au rythme du défilement, le temps que sa section passe. */
const drawLine: Preset = (el, { gsap }) => {
  markDone(el);
  gsap.fromTo(
    el,
    { scaleX: 0 },
    {
      scaleX: 1,
      ease: 'none',
      scrollTrigger: { trigger: el, start: 'top 85%', end: 'top 35%', scrub: 0.4 },
    },
  );
};

export default drawLine;
