import type { UiKey } from '@/lib/i18n';

export interface NavItem {
  key: UiKey;
  /** Chemin neutre (sans préfixe de locale). */
  path: string;
}

export const MAIN_NAV: NavItem[] = [
  { key: 'nav.services', path: '/services/' },
  { key: 'nav.ai', path: '/services/ai/' },
  { key: 'nav.projects', path: '/projects/' },
  { key: 'nav.technology', path: '/technology/' },
  { key: 'nav.about', path: '/about/' },
];

/**
 * Entrée du menu correspondant à la page (chemin sans locale) : la plus précise l'emporte,
 * pour que /services/ai/ n'allume que « IA », et pas aussi « Services ».
 */
export function activeNavPath(current: string): string | undefined {
  return MAIN_NAV.map((item) => item.path)
    .filter((path) => current.startsWith(path))
    .sort((a, b) => b.length - a.length)[0];
}

export const CONTACT_PATH = '/contact/';
export const LEGAL_PATH = '/legal/';
export const PRIVACY_PATH = '/privacy-policy/';
