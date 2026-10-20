import { SITE } from './site';
import { PAGES, fill, perLocale } from './texts';

/**
 * Mentions légales et politique de confidentialité (back-office : Pages et textes › Juridique).
 *
 * Une information juridique non renseignée n'est jamais inventée : elle s'affiche « à compléter ».
 * Tant qu'il en manque une obligatoire, les deux pages sont en noindex (voir `LEGAL_COMPLETE`).
 */

const known = (value: string) => (value === '' ? undefined : value);
const { publisherInfo, hostInfo } = PAGES.legal.fr;

export const PUBLISHER = {
  name: publisherInfo.name,
  legalForm: known(publisherInfo.legalForm),
  capital: known(publisherInfo.capital),
  rcs: known(publisherInfo.rcs),
  siret: known(publisherInfo.siret),
  vat: known(publisherInfo.vat),
  director: known(publisherInfo.director),
  address: known(publisherInfo.address),
  phone: known(publisherInfo.phone),
  email: SITE.email,
};

export const HOST = {
  name: known(hostInfo.name),
  address: known(hostInfo.address),
  phone: known(hostInfo.phone),
};

export const LEGAL_COMPLETE = [
  PUBLISHER.legalForm,
  PUBLISHER.capital,
  PUBLISHER.rcs,
  PUBLISHER.siret,
  PUBLISHER.director,
  PUBLISHER.address,
  HOST.name,
  HOST.address,
].every((value) => value !== undefined);

export const LEGAL_PAGE = perLocale((locale) => PAGES.legal[locale]);

/** {email} : e-mail des Réglages ; {address} : siège de l'éditeur (ou « à compléter »). */
export const PRIVACY_PAGE = perLocale((locale) => {
  const copy = PAGES.privacy[locale];
  const values = {
    email: SITE.email,
    address: PUBLISHER.address ?? PAGES.legal[locale].missing.toLowerCase(),
  };
  return {
    ...copy,
    sections: copy.sections.map((section) => ({
      title: fill(section.title, values),
      paragraphs: section.paragraphs.map((text) => fill(text, values)),
      items: section.items.map((text) => fill(text, values)),
    })),
  };
});
