import { PAGES } from '@/content/texts';
import type { PageTexts } from '@/types/pages';
import type { Locale } from './config';

/**
 * Libellés d'interface (menus, boutons d'appel, pied de page, accessibilité), gérés dans le
 * back-office (Pages et textes › Menus et pied de page). Clés « groupe.nom ».
 */

type Interface = PageTexts['interface'];
type Keys<G extends keyof Interface> = `${G & string}.${keyof Interface[G] & string}`;
export type UiKey = { [G in keyof Interface]: Keys<G> }[keyof Interface];

export function useTranslations(locale: Locale) {
  const texts = PAGES.interface[locale];
  return (key: UiKey): string => {
    const [group, name] = key.split('.') as [keyof Interface, string];
    return (texts[group] as Record<string, string>)[name] ?? key;
  };
}
