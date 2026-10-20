interface ImportMetaEnv {
  readonly PUBLIC_SITE_URL?: string;
  readonly PUBLIC_API_URL?: string;
  readonly PUBLIC_TURNSTILE_SITE_KEY?: string;
  /** Contenu (build) : API à interroger si différente de PUBLIC_API_URL. */
  readonly CONTENT_API_URL?: string;
  /** Contenu (CI) : instantané JSON lu à la place de l'API. */
  readonly CONTENT_SNAPSHOT?: string;
}

interface ImportMeta {
  readonly env: ImportMetaEnv;
}
