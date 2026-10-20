import { markDone } from '../core/motion';
import type { Scene } from './types';

/**
 * Visuel du hero : les aplats s'ouvrent, les captures glissent en place, puis la pastille
 * apparaît. Une seule fois, à l'arrivée sur la page ; aucune boucle.
 */
const heroCollage: Scene = (root, { gsap }) => {
  const shapes = root.querySelectorAll('[data-piece="shape"]');
  const shots = root.querySelectorAll('[data-piece="shot"]');
  const badge = root.querySelector('[data-piece="badge"]');

  const tl = gsap.timeline({ delay: 0.25, defaults: { ease: 'power3.out' } });
  tl.from(shapes, { scale: 0.6, autoAlpha: 0, duration: 0.9, stagger: 0.12 })
    .from(shots, { y: 48, autoAlpha: 0, duration: 0.9, stagger: 0.15 }, '-=0.6')
    .from(badge, { y: 12, autoAlpha: 0, duration: 0.5 }, '-=0.3');
  markDone(root);

  return () => tl.kill();
};

export default heroCollage;
