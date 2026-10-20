import { readNumber } from '../core/motion';
import type { Preset } from './types';

/** Translation verticale liée au scroll, légère : ±4 % maximum. Attribut : data-anim-speed (0–4). */
const parallax: Preset = (el, { gsap }) => {
  const amount = Math.min(Math.abs(readNumber(el, 'animSpeed', 3)), 4);

  gsap.fromTo(
    el,
    { yPercent: -amount },
    {
      yPercent: amount,
      ease: 'none',
      scrollTrigger: { trigger: el, start: 'top bottom', end: 'bottom top', scrub: true },
    },
  );
};

export default parallax;
