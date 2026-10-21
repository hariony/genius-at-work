import { markDone } from '../core/motion';
import type { Preset } from './types';

/**
 * Défilement horizontal : sur grand écran, la section est épinglée et sa piste
 * ([data-track]) glisse vers la gauche au rythme du défilement vertical ; une barre
 * ([data-progress]) suit l'avancée. Ailleurs (mobile, tablette, mouvement réduit, sans JS) :
 * la piste défile nativement au doigt, avec aimantation sur chaque carte.
 */
const horizontal: Preset = (el, { gsap, desktop }) => {
  markDone(el);
  const track = el.querySelector<HTMLElement>('[data-track]');
  if (!desktop || !track) return;

  const distance = () => Math.max(0, track.scrollWidth - track.clientWidth);
  if (distance() === 0) return;

  el.classList.add('is-pinned');
  const progress = el.querySelector<HTMLElement>('[data-progress]');
  const timeline = gsap.timeline({
    scrollTrigger: {
      trigger: el,
      start: 'center center',
      end: () => `+=${distance()}`,
      pin: true,
      scrub: 0.6,
      invalidateOnRefresh: true,
    },
  });
  timeline.to(track, { x: () => -distance(), ease: 'none' }, 0);
  if (progress) timeline.fromTo(progress, { scaleX: 0 }, { scaleX: 1, ease: 'none' }, 0);

  return () => el.classList.remove('is-pinned');
};

export default horizontal;
