export const MOTION_OK_QUERY = '(prefers-reduced-motion: no-preference)';
export const FINE_POINTER_QUERY = '(hover: hover) and (pointer: fine)';
export const DESKTOP_QUERY = '(min-width: 64rem)';

/** Événement émis sur document quand la mise en page change sans navigation. */
export const LAYOUT_CHANGE_EVENT = 'gaw:layout-change';

/** Attribut posé sur un élément dont l'état initial est désormais piloté par GSAP (ou affiché). */
export const DONE_ATTR = 'data-anim-done';

export function markDone(el: Element): void {
  el.setAttribute(DONE_ATTR, '');
}

/** Affiche tout le contenu en attente : utilisé en mode réduit et par le garde-fou. */
export function revealAll(root: ParentNode = document): void {
  root.querySelectorAll('[data-anim], [data-scene]').forEach(markDone);
}

export function readNumber(el: HTMLElement, key: string, fallback: number): number {
  const raw = el.dataset[key];
  const value = raw === undefined ? Number.NaN : Number.parseFloat(raw);
  return Number.isFinite(value) ? value : fallback;
}
