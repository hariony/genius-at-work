import { markDone } from '../core/motion';
import type { Scene } from './types';

/**
 * Chaîne complète de l'offre IA (sections/ai/AiChain.astro) : le trait de progression suit le
 * défilement et allume les étapes qu'il atteint. Aucun épinglage : la page défile normalement.
 * Sans animation, la chaîne est affichée entière (--chain-progress vaut 1 par défaut).
 */
const aiChain: Scene = (root, { ScrollTrigger }) => {
  const steps = Array.from(root.querySelectorAll<HTMLElement>('[data-chain-step]'));
  const update = (progress: number) => {
    root.style.setProperty('--chain-progress', progress.toFixed(3));
    steps.forEach((step, i) =>
      step.toggleAttribute('data-lit', progress * steps.length > i + 0.15),
    );
  };

  update(0);
  root.setAttribute('data-chain-live', '');
  markDone(root);
  const trigger = ScrollTrigger.create({
    trigger: root,
    start: 'top 75%',
    end: 'bottom 55%',
    onUpdate: ({ progress }) => update(progress),
  });

  return () => {
    trigger.kill();
    root.style.removeProperty('--chain-progress');
    root.removeAttribute('data-chain-live');
    steps.forEach((step) => step.removeAttribute('data-lit'));
  };
};

export default aiChain;
