import { PAGES, perLocale } from './texts';

/** Page Contact et textes du formulaire (back-office : Pages et textes › Contact, Formulaire de contact). */

export const CONTACT_PAGE = perLocale((locale) => PAGES.contact[locale]);

export const CONTACT_FORM = perLocale((locale) => PAGES['contact-form'][locale]);
