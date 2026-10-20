import type { ContactFormCopy } from '@/types/contact-form';
import type { StackLayer } from '@/types/content';

/**
 * Textes des pages, gérés dans le back-office (/admin/content/pages), une version par langue.
 * Même structure que le catalogue de l'API (apps/api/src/Application/Content/Page/PageCatalog.php),
 * qui valide chaque enregistrement ; l'instantané de l'API (tests) garantit la correspondance.
 * {n}, {email}, {address}… sont des emplacements remplacés à l'affichage.
 */

export interface PageHero {
  seoTitle: string;
  seoDescription: string;
  eyebrow: string;
  title: string;
  lead: string;
}

interface Chapter {
  label: string;
  title: string;
  intro: string;
}

interface Item {
  title: string;
  description: string;
}

export interface LegalSection {
  title: string;
  paragraphs: string[];
  items: string[];
}

export interface PageTexts {
  home: {
    hero: PageHero & {
      titleHighlight: string;
      scroll: string;
      visual: {
        caption: string;
        hint: string;
        clock: string;
        moments: Record<'dawn' | 'day' | 'dusk' | 'night', string>;
      };
    };
    sections: {
      hero: { badge: string };
      work: Chapter & { all: string; visit: string };
      manifesto: {
        label: string;
        text: string;
        stats: { value: number; suffix: string; label: string }[];
      };
      services: Chapter;
      engineering: Chapter & { cta: string };
      method: Chapter;
      reasons: { label: string; title: string };
    };
  };
  about: {
    hero: PageHero;
    story: { label: string; title: string; paragraphs: string[] };
    values: { label: string; title: string; items: Item[] };
    offices: Chapter;
  };
  'services-page': {
    hero: PageHero;
    list: Chapter;
    detail: {
      scopeLabel: string;
      scopeTitle: string;
      techLabel: string;
      techTitle: string;
      projectsLabel: string;
      projectsTitle: string;
      methodLabel: string;
      otherServices: string;
      seoSuffix: string;
    };
  };
  'projects-page': {
    hero: PageHero;
    filterLabel: string;
    all: string;
    countOne: string;
    countMany: string;
    detail: {
      client: string;
      clientTypes: Record<'company' | 'institution' | 'ngo' | 'in-house', string>;
      period: string;
      categories: string;
      technologies: string;
      services: string;
      visit: string;
      overview: string;
      previous: string;
      next: string;
      back: string;
      more: string;
    };
  };
  'ai-page': {
    hero: PageHero & { secondaryCta: string };
    capabilities: Chapter & { items: (Item & { examples: string[] })[] };
    useCases: Chapter & { items: Item[] };
    chain: Chapter & { steps: Item[] };
    approach: { label: string; title: string; not: string[]; answer: string };
    technologies: Chapter & { groups: { title: string; technologies: string[] }[] };
    method: Chapter;
    caseStudies: Chapter;
    cta: { label: string; title: string; text: string; button: string };
  };
  'ai-section': Chapter & {
    input: string;
    output: string;
    cta: string;
    lanes: { title: string; caption: string; examples: string[] }[];
  };
  'technology-page': {
    hero: PageHero;
    usage: Chapter & { projectsOne: string; projectsMany: string };
    principles: { label: string; title: string; items: Item[] };
  };
  contact: PageHero & {
    direct: { label: string; email: string; phones: string };
    offices: Chapter;
  };
  'contact-form': ContactFormCopy;
  'not-found': PageHero & { links: { label: string; href: string }[] };
  method: { steps: Item[]; reasons: Item[] };
  technologies: {
    stack: { layer: StackLayer; title: string; role: string; technologies: string[] }[];
  };
  interface: {
    nav: Record<
      | 'home'
      | 'services'
      | 'ai'
      | 'projects'
      | 'technology'
      | 'about'
      | 'contact'
      | 'menu'
      | 'close',
      string
    >;
    cta: Record<'discussProject' | 'seeProjects', string>;
    footer: Record<
      | 'rights'
      | 'legal'
      | 'privacy'
      | 'cta'
      | 'ctaLabel'
      | 'ctaText'
      | 'tagline'
      | 'navigation'
      | 'offices'
      | 'follow'
      | 'localTime'
      | 'backToTop',
      string
    >;
    office: Record<'coordinates', string>;
    a11y: Record<
      'skipToContent' | 'mainNav' | 'languageSwitcher' | 'breadcrumb' | 'newTab',
      string
    >;
  };
  legal: {
    seoTitle: string;
    seoDescription: string;
    title: string;
    missing: string;
    publisherInfo: Record<
      'name' | 'legalForm' | 'capital' | 'rcs' | 'siret' | 'vat' | 'director' | 'address' | 'phone',
      string
    >;
    hostInfo: Record<'name' | 'address' | 'phone', string>;
    publisher: {
      title: string;
      labels: Record<
        | 'name'
        | 'legalForm'
        | 'capital'
        | 'rcs'
        | 'siret'
        | 'vat'
        | 'director'
        | 'address'
        | 'phone'
        | 'email',
        string
      >;
    };
    host: { title: string; labels: Record<'name' | 'address' | 'phone', string> };
    sections: LegalSection[];
  };
  privacy: {
    seoTitle: string;
    seoDescription: string;
    title: string;
    updated: string;
    sections: LegalSection[];
  };
}

export type PageId = keyof PageTexts;
