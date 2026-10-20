import { markDone, readNumber } from '../core/motion';
import type { Preset } from './types';

/** Média dévoilé de haut en bas (clip-path), avec un léger dézoom de l'image intérieure. */
const revealImage: Preset = (el, { gsap }) => {
  const media = el.querySelector('img, video, picture');
  const tl = gsap.timeline({
    delay: readNumber(el, 'animDelay', 0),
    scrollTrigger: { trigger: el, start: 'top 85%', once: true },
  });

  tl.fromTo(
    el,
    { clipPath: 'inset(0% 0% 100% 0%)' },
    { clipPath: 'inset(0% 0% 0% 0%)', duration: 1, ease: 'power3.inOut', clearProps: 'clipPath' },
  );
  if (media) {
    tl.fromTo(
      media,
      { scale: 1.06 },
      { scale: 1, duration: 1.2, ease: 'power2.out', clearProps: 'transform' },
      0,
    );
  }
  markDone(el);
};

export default revealImage;
