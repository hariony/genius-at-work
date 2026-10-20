import { z } from 'zod';

// Pas de compilation à la volée : la sonde Function('') de zod serait bloquée par la CSP du site.
// Doit précéder la construction des schémas.
z.config({ jitless: true });

/**
 * Contrat de la demande de contact, partagé entre le formulaire (validation immédiate)
 * et la documentation de l'API. Le serveur Symfony applique les mêmes règles et fait foi :
 * toute modification ici doit être reportée dans apps/api (ContactRequestInput).
 */

export const PROJECT_TYPES = [
  'website',
  'mobile-app',
  'web-app',
  'business-software',
  'e-commerce',
  'ai',
  'maintenance',
  'other',
] as const;

export const BUDGET_RANGES = ['lt-10k', '10k-30k', '30k-80k', 'gt-80k', 'undefined'] as const;

export const TIMELINES = ['asap', '1-3-months', '3-6-months', 'flexible'] as const;

export const CONTACT_LIMITS = {
  nameMin: 2,
  nameMax: 120,
  companyMax: 120,
  phoneMax: 30,
  messageMin: 20,
  messageMax: 2000,
  /** Délai minimal entre l'affichage du formulaire et l'envoi (anti-robot), en ms. */
  minFillTime: 3000,
} as const;

/** Codes d'erreur stables, traduits côté interface. */
export type ContactErrorCode =
  | 'required'
  | 'too_short'
  | 'too_long'
  | 'invalid_email'
  | 'invalid_phone'
  | 'invalid_choice'
  | 'consent_required';

const required = (code: ContactErrorCode = 'required') => ({ error: code });

export const contactRequestSchema = z.object({
  projectType: z.enum(PROJECT_TYPES, required('invalid_choice')),
  budgetRange: z.enum(BUDGET_RANGES, required('invalid_choice')).optional(),
  timeline: z.enum(TIMELINES, required('invalid_choice')).optional(),
  message: z
    .string(required())
    .trim()
    .min(CONTACT_LIMITS.messageMin, { error: 'too_short' })
    .max(CONTACT_LIMITS.messageMax, { error: 'too_long' }),
  fullName: z
    .string(required())
    .trim()
    .min(CONTACT_LIMITS.nameMin, { error: 'too_short' })
    .max(CONTACT_LIMITS.nameMax, { error: 'too_long' }),
  email: z
    .string(required())
    .trim()
    .pipe(z.email({ error: 'invalid_email' })),
  phone: z
    .string()
    .trim()
    .max(CONTACT_LIMITS.phoneMax, { error: 'too_long' })
    .regex(/^\+?[0-9 ().-]{6,}$/, { error: 'invalid_phone' })
    .optional()
    .or(z.literal('')),
  company: z.string().trim().max(CONTACT_LIMITS.companyMax, { error: 'too_long' }).optional(),
  consent: z.literal(true, { error: 'consent_required' }),
  locale: z.enum(['fr', 'en']),
  sourceUrl: z.string().max(500).optional(),
  /** Champ piège invisible : doit rester vide. */
  website: z.string().max(0).optional(),
  /** Horodatage d'affichage du formulaire (ms). */
  startedAt: z.number().int().positive(),
  turnstileToken: z.string().min(1),
});

export type ContactRequest = z.infer<typeof contactRequestSchema>;
export type ProjectType = (typeof PROJECT_TYPES)[number];
export type BudgetRange = (typeof BUDGET_RANGES)[number];
export type Timeline = (typeof TIMELINES)[number];

/** Réponse 201 de l'API. */
export interface ContactRequestCreated {
  reference: string;
}

/**
 * Réponse 422 de l'API (format API Platform) : `message` porte le code stable
 * (ContactErrorCode, ou « bot_check_failed » sur turnstileToken).
 */
export interface ContactRequestRejected {
  violations: { propertyPath: string; message: ContactErrorCode | 'bot_check_failed' | string }[];
}
