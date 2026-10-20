export const LOCALES = ['fr', 'en'] as const;
export type Locale = (typeof LOCALES)[number];

export const DEFAULT_LOCALE: Locale = 'fr';

/** Valeurs utilisées pour <html lang>, og:locale et hreflang. */
export const LOCALE_META: Record<Locale, { htmlLang: string; ogLocale: string; label: string }> = {
  fr: { htmlLang: 'fr', ogLocale: 'fr_FR', label: 'Français' },
  en: { htmlLang: 'en', ogLocale: 'en_GB', label: 'English' },
};

export function isLocale(value: unknown): value is Locale {
  return typeof value === 'string' && (LOCALES as readonly string[]).includes(value);
}

export function toLocale(value: string | undefined): Locale {
  return isLocale(value) ? value : DEFAULT_LOCALE;
}
