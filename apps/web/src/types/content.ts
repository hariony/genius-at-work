import type { ImageMetadata } from 'astro';
import type { Locale } from '@/lib/i18n';

/**
 * Types du contenu éditorial. La migration vers le back-office (contenu lu sur l'API)
 * ne touche que la couche services.
 */

export type Localized<T = string> = Record<Locale, T>;

/** Image locale (import Astro) ou distante (API du back-office, dimensions connues au build). */
export type ContentImage = ImageMetadata | { src: string; width: number; height: number };

export type ProjectCategory =
  'web-app' | 'mobile' | 'ux-ui' | 'e-commerce' | 'innovation' | 'showcase' | 'travel' | 'ai';

export type ClientType = 'company' | 'institution' | 'ngo' | 'in-house';

export interface Project {
  slug: string;
  title: string;
  client: string | undefined;
  clientType: ClientType | undefined;
  /** Années de début et de fin, si connues. */
  period: { start: number; end: number } | undefined;
  categories: ProjectCategory[];
  technologies: string[];
  url: string | undefined;
  summary: Localized;
  cover: ContentImage;
  coverAlt: Localized;
  featured: boolean;
}

export type ServiceSlug =
  | 'websites'
  | 'mobile-apps'
  | 'web-apps'
  | 'business-software'
  | 'e-commerce'
  | 'ai'
  | 'maintenance-hosting';

export interface Service {
  slug: ServiceSlug;
  title: Localized;
  summary: Localized;
  /** Texte d'introduction de la page service. */
  intro: Localized;
  /** Ce que couvre le service (proposition à valider avec Genius At Work). */
  scope: Localized<string[]>;
  technologies: string[];
  relatedProjects: string[];
}

export type StackLayer =
  'frontend' | 'cms' | 'api' | 'backend' | 'data' | 'infrastructure' | 'cicd';

export interface StackGroup {
  layer: StackLayer;
  title: Localized;
  role: Localized;
  technologies: string[];
}

export interface ProcessStep {
  title: Localized;
  description: Localized;
}

export interface Reason {
  title: Localized;
  description: Localized;
}
