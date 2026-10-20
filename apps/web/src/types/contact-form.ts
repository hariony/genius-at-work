import type { BudgetRange, ContactErrorCode, ProjectType, Timeline } from '@gaw/types/contact';

/** Textes du formulaire de contact, transmis à l'îlot React (qui n'a pas accès à Astro). */
export interface ContactFormCopy {
  steps: { title: string; description: string }[];
  stepCounter: string; // « Étape {current} sur {total} »
  projectType: { legend: string; options: Record<ProjectType, string> };
  message: { label: string; hint: string; counter: string };
  budget: { legend: string; options: Record<BudgetRange, string> };
  timeline: { legend: string; options: Record<Timeline, string> };
  optional: string;
  fullName: string;
  email: string;
  phone: string;
  company: string;
  consent: { before: string; link: string; after: string };
  honeypot: string;
  turnstile: { label: string; pending: string; failed: string };
  actions: { next: string; previous: string; submit: string; submitting: string; restart: string };
  errors: Record<ContactErrorCode, string> & {
    summary: string;
    rateLimited: string;
    network: string;
    server: string;
  };
  success: { title: string; reference: string; body: string; next: string; projects: string };
}
