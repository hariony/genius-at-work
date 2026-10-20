import { markDone, readNumber } from '../core/motion';
import type { Preset } from './types';

/**
 * « Couches » : les enfants directs se posent l'un après l'autre, chacun glissant de sous le
 * précédent comme une couche de la stack. Joué une fois. Attribut : data-anim-each (intervalle, s).
 */
const stack: Preset = (el, { gsap }) => {
  const items = Array.from(el.children);
  if (items.length === 0) return;

  gsap.fromTo(
    items,
    { autoAlpha: 0, y: 40, clipPath: 'inset(0 0 100% 0)' },
    {
      autoAlpha: 1,
      y: 0,
      clipPath: 'inset(0 0 0% 0)',
      duration: 0.9,
      ease: 'expo.out',
      stagger: readNumber(el, 'animEach', 0.09),
      clearProps: 'transform,clipPath',
      scrollTrigger: { trigger: el, start: 'top 80%', once: true },
    },
  );
  markDone(el);
};

export default stack;
