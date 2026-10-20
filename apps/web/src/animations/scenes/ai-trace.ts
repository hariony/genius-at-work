import { markDone } from '../core/motion';
import type { Scene } from './types';

/**
 * Schéma « trace » de l'offre IA (components/sections/AiTrace.astro) : les nœuds apparaissent,
 * les liaisons se dessinent, puis une impulsion par voie circule de l'entrée à la sortie en
 * allumant les nœuds traversés. La boucle ne tourne que lorsque le schéma est à l'écran.
 */
const aiTrace: Scene = (root, { gsap, ScrollTrigger }) => {
  const paths = Array.from(root.querySelectorAll<SVGPathElement>('[data-trace-path]'));
  const nodes = root.querySelectorAll('[data-trace-node]');
  const pulses = Array.from(root.querySelectorAll<SVGCircleElement>('[data-trace-pulse]'));
  const path = (id: string) => paths.find((p) => p.dataset.tracePath === id);
  const node = (id: string) => root.querySelector(`[data-trace-node="${id}"]`);

  for (const p of paths) {
    const length = p.getTotalLength();
    gsap.set(p, { strokeDasharray: length, strokeDashoffset: length });
  }

  const intro = gsap.timeline({ paused: true });
  intro
    .from(nodes, { autoAlpha: 0, y: 8, duration: 0.5, stagger: 0.08 })
    .to(
      paths,
      { strokeDashoffset: 0, duration: 0.9, stagger: 0.05, ease: 'power2.inOut' },
      '-=0.2',
    );
  markDone(root);

  // Une impulsion par voie, décalées : entrée → voie → sortie.
  const loop = gsap.timeline({ paused: true, repeat: -1, repeatDelay: 0.6 });
  pulses.forEach((pulse, i) => {
    const into = path(`in-${i}`);
    const out = path(`out-${i}`);
    const lane = node(`lane-${i}`);
    if (!into || !out || !lane) return;
    const start = i * 1.1;
    const follow = (segment: SVGPathElement) => {
      const length = segment.getTotalLength();
      const progress = { t: 0 };
      return gsap.to(progress, {
        t: 1,
        duration: 0.9,
        ease: 'power1.inOut',
        onUpdate: () => {
          const point = segment.getPointAtLength(progress.t * length);
          pulse.setAttribute('cx', String(point.x));
          pulse.setAttribute('cy', String(point.y));
        },
      });
    };
    loop
      .set(pulse, { opacity: 1 }, start)
      .call(() => node('input')?.toggleAttribute('data-active', true), [], start)
      .call(() => node('input')?.removeAttribute('data-active'), [], start + 0.3)
      .add(follow(into), start)
      .call(() => lane.toggleAttribute('data-active', true), [], start + 0.9)
      .add(follow(out), start + 1.3)
      .call(() => lane.removeAttribute('data-active'), [], start + 1.5)
      .call(() => node('output')?.toggleAttribute('data-active', true), [], start + 2.2)
      .call(() => node('output')?.removeAttribute('data-active'), [], start + 2.6)
      .set(pulse, { opacity: 0 }, start + 2.2);
  });

  const trigger = ScrollTrigger.create({
    trigger: root,
    start: 'top 85%',
    end: 'bottom top',
    onEnter: () => {
      if (intro.progress() === 0) intro.play().then(() => loop.play());
    },
    onToggle: ({ isActive }) => {
      if (intro.progress() < 1) return;
      if (isActive) loop.play();
      else loop.pause();
    },
  });

  return () => {
    trigger.kill();
    root.querySelectorAll('[data-active]').forEach((el) => el.removeAttribute('data-active'));
  };
};

export default aiTrace;
