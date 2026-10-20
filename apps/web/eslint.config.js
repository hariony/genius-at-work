// @ts-check
import js from '@eslint/js';
import astro from 'eslint-plugin-astro';
import jsxA11y from 'eslint-plugin-jsx-a11y';
import globals from 'globals';
import { defineConfig } from 'eslint/config';
import tseslint from 'typescript-eslint';

export default defineConfig(
  { ignores: ['dist/', '.astro/', 'node_modules/'] },
  js.configs.recommended,
  ...tseslint.configs.strict,
  ...astro.configs.recommended,
  ...astro.configs['jsx-a11y-strict'],
  {
    languageOptions: { globals: { ...globals.browser } },
  },
  {
    files: ['*.config.{js,mjs,ts}'],
    languageOptions: { globals: { ...globals.node } },
  },
  {
    files: ['**/*.{tsx,jsx}'],
    ...jsxA11y.flatConfigs.strict,
  },
  {
    // Les animations ne passent que par src/animations : aucun import direct de GSAP ailleurs.
    files: ['src/**/*.{ts,tsx,astro}'],
    ignores: ['src/animations/**'],
    rules: {
      'no-restricted-imports': [
        'error',
        {
          patterns: [
            {
              group: ['gsap', 'gsap/*'],
              message: 'Utiliser data-anim / data-scene (src/animations).',
            },
          ],
        },
      ],
    },
  },
  {
    // Les composants reçoivent leurs données en props : pas d'accès direct aux services de données.
    files: ['src/components/**/*'],
    rules: {
      'no-restricted-imports': [
        'error',
        { patterns: [{ group: ['@/services/*'], message: 'Passer les données en props.' }] },
      ],
    },
  },
);
