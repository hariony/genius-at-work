import {
  BUDGET_RANGES,
  CONTACT_LIMITS,
  PROJECT_TYPES,
  TIMELINES,
  contactRequestSchema,
  type ContactErrorCode,
  type ContactRequestCreated,
  type ContactRequestRejected,
} from '@gaw/types/contact';
import { useEffect, useId, useRef, useState, type ChangeEvent, type ReactNode } from 'react';
import type { ContactFormCopy } from '@/types/contact-form';

/**
 * Formulaire de contact en trois étapes (îlot React, hydraté à l'apparition).
 * Validation immédiate avec le schéma partagé @gaw/types ; l'API Symfony refait
 * toutes les vérifications et fait foi (Turnstile, piège, délai minimal, limite d'envois).
 */

interface Props {
  copy: ContactFormCopy;
  locale: 'fr' | 'en';
  apiUrl: string;
  turnstileSiteKey: string;
  privacyHref: string;
  projectsHref: string;
  fallbackEmail: string;
}

type Values = {
  projectType: string;
  message: string;
  budgetRange: string;
  timeline: string;
  fullName: string;
  email: string;
  phone: string;
  company: string;
  consent: boolean;
  website: string;
};

type Field = keyof Values;
type Errors = Partial<Record<Field, ContactErrorCode>>;
type Status =
  | { kind: 'editing' }
  | { kind: 'submitting' }
  | { kind: 'success'; reference: string }
  | { kind: 'failed'; message: string };

const STEP_FIELDS: Field[][] = [
  ['projectType', 'message'],
  ['budgetRange', 'timeline'],
  ['fullName', 'email', 'phone', 'company', 'consent'],
];

/** Service de la page d'origine (?service=…) → type de projet présélectionné. */
const SERVICE_TO_PROJECT_TYPE: Record<string, string> = {
  websites: 'website',
  'mobile-apps': 'mobile-app',
  'web-apps': 'web-app',
  'business-software': 'business-software',
  'e-commerce': 'e-commerce',
  ai: 'ai',
  'maintenance-hosting': 'maintenance',
};

const INITIAL: Values = {
  projectType: '',
  message: '',
  budgetRange: '',
  timeline: '',
  fullName: '',
  email: '',
  phone: '',
  company: '',
  consent: false,
  website: '',
};

const format = (template: string, vars: Record<string, string | number>) =>
  template.replace(/\{(\w+)\}/g, (_, key: string) => String(vars[key] ?? ''));

const isErrorCode = (code: string, copy: ContactFormCopy): code is ContactErrorCode =>
  code in copy.errors;

/** Valeurs du formulaire → charge utile de l'API (champs facultatifs vides omis). */
function toPayload(
  values: Values,
  extra: { locale: string; startedAt: number; turnstileToken: string },
) {
  const optional = (value: string) => (value.trim() === '' ? undefined : value);
  return {
    projectType: values.projectType,
    message: values.message,
    budgetRange: optional(values.budgetRange),
    timeline: optional(values.timeline),
    fullName: values.fullName,
    email: values.email,
    phone: optional(values.phone),
    company: optional(values.company),
    consent: values.consent,
    website: values.website,
    sourceUrl: window.location.pathname,
    ...extra,
  };
}

function validate(values: Values, fields: Field[], extra: Parameters<typeof toPayload>[1]): Errors {
  const result = contactRequestSchema.safeParse(toPayload(values, extra));
  if (result.success) return {};
  const errors: Errors = {};
  for (const issue of result.error.issues) {
    const field = issue.path[0] as Field;
    if (fields.includes(field) && !errors[field]) {
      errors[field] = (issue.message as ContactErrorCode) || 'required';
    }
  }
  return errors;
}

export default function ContactForm({
  copy,
  locale,
  apiUrl,
  turnstileSiteKey,
  privacyHref,
  projectsHref,
  fallbackEmail,
}: Props) {
  const [values, setValues] = useState<Values>(INITIAL);
  const [errors, setErrors] = useState<Errors>({});
  const [step, setStep] = useState(0);
  const [status, setStatus] = useState<Status>({ kind: 'editing' });
  const [startedAt] = useState(() => Date.now());
  // Faux tant que l'îlot n'est pas hydraté : le bouton reste inactif, le navigateur ne peut pas
  // envoyer le formulaire lui-même (les champs ne doivent jamais finir dans une URL).
  const [ready, setReady] = useState(false);
  const headingRef = useRef<HTMLHeadingElement>(null);
  const firstRender = useRef(true);
  const turnstile = useTurnstile(turnstileSiteKey, locale, step === STEP_FIELDS.length - 1);
  const formId = useId();

  useEffect(() => {
    setReady(true);
    const service = new URLSearchParams(window.location.search).get('service') ?? '';
    const projectType = SERVICE_TO_PROJECT_TYPE[service];
    if (projectType) setValues((current) => ({ ...current, projectType }));
  }, []);

  // Changement d'étape ou résultat : le focus suit le nouveau titre.
  useEffect(() => {
    if (firstRender.current) {
      firstRender.current = false;
      return;
    }
    headingRef.current?.focus();
  }, [step, status.kind === 'success']);

  const extra = { locale, startedAt, turnstileToken: turnstile.token ?? 'pending' };

  const set = (field: Field) => (event: ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const target = event.target;
    const value =
      target instanceof HTMLInputElement && target.type === 'checkbox'
        ? target.checked
        : target.value;
    setValues((current) => ({ ...current, [field]: value }));
    if (errors[field]) setErrors((current) => ({ ...current, [field]: undefined }));
  };

  const focusFirstError = (found: Errors) => {
    const field = (Object.keys(found) as Field[]).find((key) => found[key]);
    if (field) document.getElementById(`${formId}-${field}`)?.focus();
  };

  const goNext = () => {
    const found = validate(values, STEP_FIELDS[step] ?? [], extra);
    setErrors(found);
    if (Object.keys(found).length > 0) {
      focusFirstError(found);
      return;
    }
    setStep((current) => current + 1);
  };

  const submit = async () => {
    const found = validate(values, STEP_FIELDS.flat(), extra);
    setErrors(found);
    if (Object.keys(found).length > 0) {
      const errorStep = STEP_FIELDS.findIndex((fields) => fields.some((field) => found[field]));
      if (errorStep !== step) setStep(errorStep);
      else focusFirstError(found);
      return;
    }
    if (!turnstile.token) {
      setStatus({
        kind: 'failed',
        message: turnstile.failed ? copy.turnstile.failed : copy.turnstile.pending,
      });
      return;
    }

    setStatus({ kind: 'submitting' });
    try {
      const response = await fetch(`${apiUrl}/api/v1/contact-requests`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify(toPayload(values, { ...extra, turnstileToken: turnstile.token })),
      });

      if (response.status === 201) {
        const body = (await response.json()) as ContactRequestCreated;
        setStatus({ kind: 'success', reference: body.reference });
        return;
      }
      if (response.status === 422) {
        const body = (await response.json()) as ContactRequestRejected;
        turnstile.reset();
        if (body.violations.some((violation) => violation.propertyPath === 'turnstileToken')) {
          setStatus({ kind: 'failed', message: copy.turnstile.failed });
          return;
        }
        const serverErrors: Errors = {};
        for (const violation of body.violations) {
          const field = violation.propertyPath as Field;
          if (field in INITIAL && !serverErrors[field]) {
            serverErrors[field] = isErrorCode(violation.message, copy)
              ? violation.message
              : 'required';
          }
        }
        setErrors(serverErrors);
        const errorStep = STEP_FIELDS.findIndex((fields) =>
          fields.some((field) => serverErrors[field]),
        );
        if (errorStep >= 0) setStep(errorStep);
        setStatus({ kind: 'failed', message: copy.errors.summary });
        return;
      }
      turnstile.reset();
      setStatus({
        kind: 'failed',
        message: response.status === 429 ? copy.errors.rateLimited : copy.errors.server,
      });
    } catch {
      setStatus({ kind: 'failed', message: copy.errors.network });
    }
  };

  if (status.kind === 'success') {
    return (
      <div className="flex flex-col gap-8" role="status">
        <h2 ref={headingRef} tabIndex={-1} className="type-h2 outline-none">
          {copy.success.title}
        </h2>
        <p className="type-label text-fg-muted">
          {copy.success.reference} ·{' '}
          <span className="text-fg tabular-nums">{status.reference}</span>
        </p>
        <p className="max-w-[48ch] type-lead text-fg-muted">{copy.success.body}</p>
        <p className="flex flex-col gap-2">
          <span className="type-label text-fg-muted">{copy.success.next}</span>
          <a href={projectsHref} className="self-start type-h3">
            {copy.success.projects}
          </a>
        </p>
      </div>
    );
  }

  const total = STEP_FIELDS.length;
  const current = copy.steps[step];
  const isLast = step === total - 1;
  const submitting = status.kind === 'submitting';
  const fieldProps = (field: Field) => ({
    id: `${formId}-${field}`,
    error: errors[field] ? copy.errors[errors[field]] : undefined,
  });

  return (
    <form
      method="post"
      noValidate
      className="flex flex-col gap-10"
      aria-labelledby={`${formId}-title`}
      onSubmit={(event) => {
        event.preventDefault();
        if (isLast) void submit();
        else goNext();
      }}
    >
      <ol
        className="grid grid-cols-3 gap-2"
        aria-label={format(copy.stepCounter, { current: step + 1, total })}
      >
        {copy.steps.map((item, index) => (
          <li
            key={item.title}
            aria-current={index === step ? 'step' : undefined}
            className="flex flex-col gap-2"
          >
            <span
              className={`h-0.5 w-full ${index <= step ? 'bg-accent' : 'bg-border'}`}
              aria-hidden="true"
            />
            <span className={`type-label ${index === step ? 'text-fg' : 'text-fg-muted'}`}>
              0{index + 1} · {item.title}
            </span>
          </li>
        ))}
      </ol>

      <div className="flex flex-col gap-2">
        <p className="type-label text-fg-muted">
          {format(copy.stepCounter, { current: step + 1, total })}
        </p>
        <h2 id={`${formId}-title`} ref={headingRef} tabIndex={-1} className="type-h2 outline-none">
          {current?.title}
        </h2>
        <p className="text-fg-muted">{current?.description}</p>
      </div>

      {step === 0 && (
        <>
          <ChoiceGroup
            {...fieldProps('projectType')}
            name="projectType"
            legend={copy.projectType.legend}
            options={PROJECT_TYPES.map((value) => ({
              value,
              label: copy.projectType.options[value],
            }))}
            value={values.projectType}
            onChange={set('projectType')}
          />
          <TextArea
            {...fieldProps('message')}
            label={copy.message.label}
            hint={copy.message.hint}
            counter={format(copy.message.counter, {
              count: values.message.length,
              max: CONTACT_LIMITS.messageMax,
            })}
            value={values.message}
            maxLength={CONTACT_LIMITS.messageMax}
            onChange={set('message')}
          />
        </>
      )}

      {step === 1 && (
        <>
          <ChoiceGroup
            {...fieldProps('budgetRange')}
            name="budgetRange"
            legend={copy.budget.legend}
            optional={copy.optional}
            options={BUDGET_RANGES.map((value) => ({ value, label: copy.budget.options[value] }))}
            value={values.budgetRange}
            onChange={set('budgetRange')}
          />
          <ChoiceGroup
            {...fieldProps('timeline')}
            name="timeline"
            legend={copy.timeline.legend}
            optional={copy.optional}
            options={TIMELINES.map((value) => ({ value, label: copy.timeline.options[value] }))}
            value={values.timeline}
            onChange={set('timeline')}
          />
        </>
      )}

      {step === 2 && (
        <>
          <div className="grid gap-6 md:grid-cols-2">
            <TextField
              {...fieldProps('fullName')}
              label={copy.fullName}
              autoComplete="name"
              value={values.fullName}
              maxLength={CONTACT_LIMITS.nameMax}
              onChange={set('fullName')}
            />
            <TextField
              {...fieldProps('email')}
              label={copy.email}
              type="email"
              autoComplete="email"
              value={values.email}
              onChange={set('email')}
            />
            <TextField
              {...fieldProps('phone')}
              label={copy.phone}
              optional={copy.optional}
              type="tel"
              autoComplete="tel"
              value={values.phone}
              maxLength={CONTACT_LIMITS.phoneMax}
              onChange={set('phone')}
            />
            <TextField
              {...fieldProps('company')}
              label={copy.company}
              optional={copy.optional}
              autoComplete="organization"
              value={values.company}
              maxLength={CONTACT_LIMITS.companyMax}
              onChange={set('company')}
            />
          </div>

          {/* Piège à robots : invisible et ignoré par les technologies d'assistance. */}
          <div className="absolute -left-[9999px] h-px w-px overflow-hidden" aria-hidden="true">
            <label htmlFor={`${formId}-website`}>{copy.honeypot}</label>
            <input
              id={`${formId}-website`}
              name="website"
              type="text"
              tabIndex={-1}
              autoComplete="off"
              value={values.website}
              onChange={set('website')}
            />
          </div>

          <Checkbox {...fieldProps('consent')} checked={values.consent} onChange={set('consent')}>
            {copy.consent.before}{' '}
            <a href={privacyHref} target="_blank" rel="noopener">
              {copy.consent.link}
            </a>
            {copy.consent.after}
          </Checkbox>

          <div className="flex flex-col gap-2">
            <span className="sr-only">{copy.turnstile.label}</span>
            <div ref={turnstile.containerRef} className="min-h-[65px]" />
            {turnstile.failed && (
              <p className="text-danger" role="alert">
                {copy.turnstile.failed} <a href={`mailto:${fallbackEmail}`}>{fallbackEmail}</a>
              </p>
            )}
          </div>
        </>
      )}

      {status.kind === 'failed' && (
        <p className="rounded-sm border border-danger px-4 py-3 text-danger" role="alert">
          {status.message} <a href={`mailto:${fallbackEmail}`}>{fallbackEmail}</a>
        </p>
      )}

      <div className="flex flex-wrap items-center gap-6">
        <button
          type="submit"
          className="contact-submit"
          disabled={!ready || submitting}
          aria-disabled={!ready || submitting}
        >
          {isLast
            ? submitting
              ? copy.actions.submitting
              : copy.actions.submit
            : copy.actions.next}
          <span className="contact-submit-icon" aria-hidden="true">
            →
          </span>
        </button>
        {step > 0 && (
          <button
            type="button"
            className="underline underline-offset-4"
            onClick={() => setStep((value) => value - 1)}
            disabled={submitting}
          >
            {copy.actions.previous}
          </button>
        )}
      </div>
    </form>
  );
}

/* ---------- Champs ---------- */

interface FieldBase {
  id: string;
  error: string | undefined;
  optional?: string;
}

function FieldLabel({
  id,
  label,
  optional,
}: {
  id: string;
  label: string;
  optional?: string | undefined;
}) {
  return (
    <label htmlFor={id} className="type-label">
      {label}
      {optional && <span className="text-fg-muted"> · {optional}</span>}
    </label>
  );
}

function ErrorText({ id, error }: { id: string; error: string | undefined }) {
  return error ? (
    <p id={`${id}-error`} className="text-small text-danger">
      {error}
    </p>
  ) : null;
}

const describedBy = (...ids: (string | false | undefined)[]) =>
  ids.filter(Boolean).join(' ') || undefined;

function TextField({
  id,
  error,
  optional,
  label,
  ...input
}: FieldBase & {
  label: string;
  type?: string;
  autoComplete: string;
  value: string;
  maxLength?: number;
  onChange: (event: ChangeEvent<HTMLInputElement>) => void;
}) {
  return (
    <div className="flex flex-col gap-2">
      <FieldLabel id={id} label={label} optional={optional} />
      <input
        id={id}
        {...input}
        required={!optional}
        aria-invalid={Boolean(error)}
        aria-describedby={describedBy(error && `${id}-error`)}
        className="field"
      />
      <ErrorText id={id} error={error} />
    </div>
  );
}

function TextArea({
  id,
  error,
  label,
  hint,
  counter,
  ...textarea
}: FieldBase & {
  label: string;
  hint: string;
  counter: string;
  value: string;
  maxLength: number;
  onChange: (event: ChangeEvent<HTMLTextAreaElement>) => void;
}) {
  return (
    <div className="flex flex-col gap-2">
      <FieldLabel id={id} label={label} />
      <p id={`${id}-hint`} className="text-small text-fg-muted">
        {hint}
      </p>
      <textarea
        id={id}
        rows={7}
        required
        {...textarea}
        aria-invalid={Boolean(error)}
        aria-describedby={describedBy(`${id}-hint`, error && `${id}-error`, `${id}-counter`)}
        className="field"
      />
      <div className="flex items-start justify-between gap-4">
        <ErrorText id={id} error={error} />
        <p id={`${id}-counter`} className="ml-auto type-meta text-fg-muted tabular-nums">
          {counter}
        </p>
      </div>
    </div>
  );
}

function ChoiceGroup({
  id,
  error,
  optional,
  name,
  legend,
  options,
  value,
  onChange,
}: FieldBase & {
  name: string;
  legend: string;
  options: { value: string; label: string }[];
  value: string;
  onChange: (event: ChangeEvent<HTMLInputElement>) => void;
}) {
  return (
    <fieldset
      className="flex flex-col gap-4"
      aria-describedby={describedBy(error && `${id}-error`)}
    >
      <legend className="mb-4 type-label">
        {legend}
        {optional && <span className="text-fg-muted"> · {optional}</span>}
      </legend>
      <div className="flex flex-wrap gap-2">
        {options.map((option, index) => (
          <label key={option.value} className="contact-chip">
            <input
              // Le premier bouton porte l'identifiant : c'est lui qui reçoit le focus en cas d'erreur.
              id={index === 0 ? id : undefined}
              type="radio"
              name={name}
              value={option.value}
              checked={value === option.value}
              onChange={onChange}
              required={!optional}
              data-invalid={error ? 'true' : undefined}
              className="sr-only"
            />
            <span>{option.label}</span>
          </label>
        ))}
      </div>
      <ErrorText id={id} error={error} />
    </fieldset>
  );
}

function Checkbox({
  id,
  error,
  checked,
  onChange,
  children,
}: FieldBase & {
  checked: boolean;
  onChange: (event: ChangeEvent<HTMLInputElement>) => void;
  children: ReactNode;
}) {
  return (
    <div className="flex flex-col gap-2">
      <div className="flex items-start gap-3">
        <input
          id={id}
          type="checkbox"
          checked={checked}
          onChange={onChange}
          required
          aria-invalid={Boolean(error)}
          aria-describedby={describedBy(error && `${id}-error`)}
          className="mt-1 size-5 shrink-0 accent-[var(--surface-accent)]"
        />
        <label htmlFor={id} className="text-fg-muted">
          {children}
        </label>
      </div>
      <ErrorText id={id} error={error} />
    </div>
  );
}

/* ---------- Turnstile ---------- */

declare global {
  interface Window {
    turnstile?: {
      render: (el: HTMLElement, options: Record<string, unknown>) => string;
      reset: (widgetId: string) => void;
      remove: (widgetId: string) => void;
    };
  }
}

const TURNSTILE_SRC = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
let turnstileScript: Promise<void> | undefined;

function loadTurnstile(): Promise<void> {
  turnstileScript ??= new Promise((resolve, reject) => {
    const script = document.createElement('script');
    script.src = TURNSTILE_SRC;
    script.async = true;
    script.onload = () => resolve();
    script.onerror = () => {
      turnstileScript = undefined;
      reject(new Error('turnstile'));
    };
    document.head.append(script);
  });
  return turnstileScript;
}

/** Widget Cloudflare Turnstile, chargé seulement quand l'étape finale s'affiche. */
function useTurnstile(siteKey: string, locale: string, active: boolean) {
  const containerRef = useRef<HTMLDivElement>(null);
  const widgetId = useRef<string | undefined>(undefined);
  const [token, setToken] = useState<string | undefined>(undefined);
  const [failed, setFailed] = useState(false);

  useEffect(() => {
    if (!active) return;
    let cancelled = false;
    loadTurnstile()
      .then(() => {
        if (cancelled || !containerRef.current || !window.turnstile) return;
        widgetId.current = window.turnstile.render(containerRef.current, {
          sitekey: siteKey,
          language: locale,
          theme: 'light',
          callback: (value: string) => {
            setToken(value);
            setFailed(false);
          },
          'expired-callback': () => setToken(undefined),
          'error-callback': () => setFailed(true),
        });
      })
      .catch(() => setFailed(true));
    return () => {
      cancelled = true;
      if (widgetId.current && window.turnstile) window.turnstile.remove(widgetId.current);
      widgetId.current = undefined;
      setToken(undefined);
    };
  }, [active, siteKey, locale]);

  return {
    containerRef,
    token,
    failed,
    reset: () => {
      setToken(undefined);
      if (widgetId.current && window.turnstile) window.turnstile.reset(widgetId.current);
    },
  };
}
