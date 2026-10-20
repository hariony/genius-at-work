import { loadSiteContent } from '@/lib/content-api/load';
import type { Project, ProjectCategory, Service, ServiceSlug } from '@/types/content';

/**
 * Accès aux projets et aux services, gérés dans le back-office (/admin/content/projects et
 * /admin/content/services) et lus sur l'API au build. Seuls les projets publiés sont reçus.
 */

const { projects: PROJECTS, services: SERVICES } = (await loadSiteContent()) satisfies {
  projects: Project[];
  services: Service[];
};

/** Catégorie de projet → service correspondant. */
const CATEGORY_TO_SERVICE: Partial<Record<ProjectCategory, ServiceSlug>> = {
  showcase: 'websites',
  mobile: 'mobile-apps',
  'web-app': 'web-apps',
  'e-commerce': 'e-commerce',
  ai: 'ai',
};

export function getProjects(): Project[] {
  return PROJECTS;
}

export function getFeaturedProjects(): Project[] {
  return PROJECTS.filter((project) => project.featured);
}

export function getProject(slug: string): Project | undefined {
  return PROJECTS.find((project) => project.slug === slug);
}

export function getServices(): Service[] {
  return SERVICES;
}

export function getService(slug: string): Service | undefined {
  return SERVICES.find((service) => service.slug === slug);
}

export function servicesForProject(project: Project): Service[] {
  const fromCategories = new Set(
    project.categories.map((category) => CATEGORY_TO_SERVICE[category]),
  );
  return SERVICES.filter(
    (service) => service.relatedProjects.includes(project.slug) || fromCategories.has(service.slug),
  );
}

/** Projets liés : d'abord ceux choisis pour le service, puis ceux de la catégorie correspondante. */
export function projectsForService(service: Service): Project[] {
  const curated = service.relatedProjects
    .map((slug) => getProject(slug))
    .filter((project) => project !== undefined);
  const others = PROJECTS.filter(
    (project) =>
      !curated.includes(project) &&
      servicesForProject(project).some((related) => related.slug === service.slug),
  );
  return [...curated, ...others];
}

/** Projets voisins dans l'ordre du portfolio (boucle). */
export function adjacentProjects(project: Project): { previous: Project; next: Project } {
  const index = PROJECTS.indexOf(project);
  const at = (i: number) => PROJECTS[(i + PROJECTS.length) % PROJECTS.length] ?? project;
  return { previous: at(index - 1), next: at(index + 1) };
}

/** Technologies réellement livrées, triées par nombre de projets. */
export function technologyUsage(): { name: string; projects: Project[] }[] {
  const usage = new Map<string, Project[]>();
  for (const project of PROJECTS) {
    for (const tech of project.technologies) {
      usage.set(tech, [...(usage.get(tech) ?? []), project]);
    }
  }
  return [...usage.entries()]
    .map(([name, projects]) => ({ name, projects }))
    .sort((a, b) => b.projects.length - a.projects.length || a.name.localeCompare(b.name));
}

/**
 * Catégories proposées comme filtres, dans l'ordre de la taxonomie.
 * « Innovation » (11 projets sur 15) reste une étiquette mais ne filtre rien d'utile (audit §5.3).
 */
export function filterCategories(): ProjectCategory[] {
  const order: ProjectCategory[] = [
    'web-app',
    'mobile',
    'ux-ui',
    'e-commerce',
    'showcase',
    'travel',
    'ai',
  ];
  return order.filter((category) =>
    PROJECTS.some((project) => project.categories.includes(category)),
  );
}
