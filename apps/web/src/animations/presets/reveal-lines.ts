import { markDone, readNumber } from '../core/motion';
import type { Preset } from './types';

/**
 * Titre découpé en lignes masquées qui montent une à une.
 * SplitText conserve le texte d'origine pour les lecteurs d'écran (aria: 'auto')
 * et redécoupe automatiquement au chargement des polices et au redimensionnement.
 * Réservé aux titres (h1–h6) : SplitText y pose un aria-label, interdit sur un <p>.
 * Attributs : data-anim-delay, data-anim-trigger="load" (au chargement plutôt qu'au scroll).
 */
const revealLines: Preset = (el, { gsap, SplitText }) => {
  if (!SplitText) {
    markDone(el);
    return;
  }

  const delay = readNumber(el, 'animDelay', 0);
  const onScroll = el.dataset.animTrigger !== 'load';

  const split = SplitText.create(el, {
    type: 'lines',
    mask: 'lines',
    linesClass: 'split-line',
    autoSplit: true,
    onSplit(self) {
      markDone(el);
      return gsap.from(self.lines, {
        yPercent: 100,
        duration: 0.8,
        stagger: 0.06,
        ease: 'power3.out',
        delay,
        ...(onScroll && { scrollTrigger: { trigger: el, start: 'top 85%', once: true } }),
      });
    },
  });

  return () => split.revert();
};

export default revealLines;
