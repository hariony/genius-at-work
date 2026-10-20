import type { gsap as GsapInstance } from 'gsap';
import type { ScrollTrigger as ScrollTriggerPlugin } from 'gsap/ScrollTrigger';
import type { SplitText as SplitTextPlugin } from 'gsap/SplitText';

export type Gsap = typeof GsapInstance;
export type ScrollTrigger = typeof ScrollTriggerPlugin;
export type SplitText = typeof SplitTextPlugin;

export interface MotionKit {
  gsap: Gsap;
  ScrollTrigger: ScrollTrigger;
}

let corePromise: Promise<MotionKit> | undefined;
let splitPromise: Promise<SplitText> | undefined;

/** GSAP + ScrollTrigger, chargés une seule fois et à la demande (jamais dans le bundle initial). */
export function loadMotionKit(): Promise<MotionKit> {
  corePromise ??= Promise.all([import('gsap'), import('gsap/ScrollTrigger')]).then(
    ([{ gsap }, { ScrollTrigger }]) => {
      gsap.registerPlugin(ScrollTrigger);
      gsap.defaults({ ease: 'power3.out', duration: 0.7 });
      return { gsap, ScrollTrigger };
    },
  );
  return corePromise;
}

/** SplitText n'est chargé que si la page contient des titres à découper. */
export function loadSplitText(): Promise<SplitText> {
  splitPromise ??= Promise.all([loadMotionKit(), import('gsap/SplitText')]).then(
    ([{ gsap }, { SplitText }]) => {
      gsap.registerPlugin(SplitText);
      return SplitText;
    },
  );
  return splitPromise;
}
