import { markDone, readNumber } from '../core/motion';
import type { Preset } from './types';

/** Enfants directs révélés en cascade. Attributs : data-anim-each (intervalle, s). */
const stagger: Preset = (el, { gsap }) => {
  const items = Array.from(el.children);
  if (items.length === 0) return;

  gsap.fromTo(
    items,
    { autoAlpha: 0, y: 12 },
    {
      autoAlpha: 1,
      y: 0,
      duration: 0.5,
      stagger: readNumber(el, 'animEach', 0.06),
      clearProps: 'transform',
      scrollTrigger: { trigger: el, start: 'top 85%', once: true },
    },
  );
  markDone(el);
};

export default stagger;
