import { loadMotionKit, loadSplitText } from './core/gsap';
import { DESKTOP_QUERY, FINE_POINTER_QUERY, MOTION_OK_QUERY, revealAll } from './core/motion';
import parallax from './presets/parallax';
import revealImage from './presets/reveal-image';
import revealLines from './presets/reveal-lines';
import revealUp from './presets/reveal-up';
import stagger from './presets/stagger';
import type { Preset, PresetEnv } from './presets/types';
import type { Scene } from './scenes/types';

/**
 * Point d'entrée unique des animations.
 * Les composants déclarent `data-anim="<preset>"` ou `data-scene="<scène>"` ; ce contrôleur
 * instancie les animations correspondantes et les détruit à chaque navigation.
 */

const PRESETS: Record<string, Preset> = {
  'reveal-up': revealUp,
  'reveal-lines': revealLines,
  'reveal-image': revealImage,
  stagger,
  parallax,
};

/**
 * Scènes chargées seulement si la page en contient. Philosophie : peu d'animations, jouées
 * une seule fois à l'entrée dans l'écran ; aucune boucle permanente, aucun défilement détourné.
 */
const SCENES: Record<string, () => Promise<{ default: Scene }>> = {
  'hero-collage': () => import('./scenes/hero-collage'),
  'ai-trace': () => import('./scenes/ai-trace'),
  'ai-chain': () => import('./scenes/ai-chain'),
};

type MatchMedia = ReturnType<PresetEnv['gsap']['matchMedia']>;

let active: MatchMedia | undefined;

export async function mountAnimations(root: ParentNode = document): Promise<void> {
  const animated = Array.from(root.querySelectorAll<HTMLElement>('[data-anim]'));
  const sceneEls = Array.from(root.querySelectorAll<HTMLElement>('[data-scene]'));
  if (animated.length === 0 && sceneEls.length === 0) return;

  const needsSplit = animated.some((el) => el.dataset.anim === 'reveal-lines');
  const [kit, SplitText, scenes] = await Promise.all([
    loadMotionKit(),
    needsSplit ? loadSplitText() : Promise.resolve(undefined),
    loadScenes(sceneEls),
  ]);

  unmountAnimations();
  const mm = kit.gsap.matchMedia();
  active = mm;

  mm.add(
    { motionOk: MOTION_OK_QUERY, finePointer: FINE_POINTER_QUERY, desktop: DESKTOP_QUERY },
    (context) => {
      const conditions = context.conditions ?? {};
      if (!conditions['motionOk']) {
        revealAll(root);
        return;
      }

      const env: PresetEnv = {
        ...kit,
        SplitText,
        finePointer: Boolean(conditions['finePointer']),
        desktop: Boolean(conditions['desktop']),
      };
      const cleanups: (() => void)[] = [];

      for (const el of animated) {
        const preset = PRESETS[el.dataset.anim ?? ''];
        const cleanup = preset?.(el, env);
        if (cleanup) cleanups.push(cleanup);
      }
      for (const { el, scene } of scenes) {
        const cleanup = scene(el, env);
        if (cleanup) cleanups.push(cleanup);
      }

      kit.ScrollTrigger.refresh();
      return () => cleanups.forEach((fn) => fn());
    },
  );
}

export function unmountAnimations(): void {
  active?.revert();
  active = undefined;
}

async function loadScenes(els: HTMLElement[]): Promise<{ el: HTMLElement; scene: Scene }[]> {
  const loaded = await Promise.all(
    els.map(async (el) => {
      const loader = SCENES[el.dataset.scene ?? ''];
      return loader ? { el, scene: (await loader()).default } : undefined;
    }),
  );
  return loaded.filter((entry) => entry !== undefined);
}
