import { PAGES, perLocale, plural } from './texts';

/**
 * Textes des pages intérieures (Services, Réalisations, Technologie, À propos), gérés dans le
 * back-office (Pages et textes).
 */

export const SERVICES_PAGE = perLocale((locale) => PAGES['services-page'][locale]);

export const PROJECTS_PAGE = perLocale((locale) => {
  const { countOne, countMany, ...copy } = PAGES['projects-page'][locale];
  return { ...copy, count: plural(countOne, countMany) };
});

export const TECHNOLOGY_PAGE = perLocale((locale) => {
  const { usage, ...copy } = PAGES['technology-page'][locale];
  const { projectsOne, projectsMany, ...rest } = usage;
  return { ...copy, usage: { ...rest, projects: plural(projectsOne, projectsMany) } };
});

export const ABOUT_PAGE = perLocale((locale) => PAGES.about[locale]);
