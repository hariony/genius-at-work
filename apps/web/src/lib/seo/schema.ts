import { SITE } from '@/content/site';
import { LOCALE_META, type Locale } from '@/lib/i18n';

/**
 * Entités schema.org construites uniquement à partir des informations publiées
 * (content/site.ts). L'organisation a un identifiant stable (#organization)
 * auquel les autres entités font référence.
 */

export const organizationId = (site: URL) => new URL('/#organization', site).href;

export function organization(site: URL, locale: Locale) {
  return {
    '@type': 'Organization',
    '@id': organizationId(site),
    name: SITE.name,
    url: new URL('/', site).href,
    email: SITE.email,
    sameAs: SITE.socials.map((social) => social.href),
    address: SITE.offices.map((office) => ({
      '@type': 'PostalAddress',
      name: office.name[locale],
      streetAddress: office.addressLines.slice(0, -1).join(', '),
      addressLocality: office.city,
      addressCountry: office.id === 'france' ? 'FR' : 'MG',
    })),
    contactPoint: SITE.offices.flatMap((office) =>
      office.phones.map((phone) => ({
        '@type': 'ContactPoint',
        telephone: phone.e164,
        contactType: 'customer service',
        areaServed: office.id === 'france' ? 'FR' : 'MG',
        availableLanguage: ['fr', 'en'],
      })),
    ),
  };
}

export function website(site: URL, locale: Locale) {
  return {
    '@type': 'WebSite',
    '@id': new URL('/#website', site).href,
    name: SITE.name,
    url: new URL('/', site).href,
    inLanguage: LOCALE_META[locale].htmlLang,
    publisher: { '@id': organizationId(site) },
  };
}

export function breadcrumbs(site: URL, items: { label: string; href: string }[]) {
  return {
    '@type': 'BreadcrumbList',
    itemListElement: items.map((item, index) => ({
      '@type': 'ListItem',
      position: index + 1,
      name: item.label,
      item: new URL(item.href, site).href,
    })),
  };
}
