import type { APIRoute } from 'astro';

/** robots.txt : tout est indexable sauf le styleguide interne ; lien vers le sitemap. */
export const GET: APIRoute = ({ site }) => {
  const sitemap = new URL('/sitemap-index.xml', site ?? 'https://genius-at-work.com').href;
  const body = ['User-agent: *', 'Disallow: /design-system/', '', `Sitemap: ${sitemap}`, ''];
  return new Response(body.join('\n'), {
    headers: { 'Content-Type': 'text/plain; charset=utf-8' },
  });
};
