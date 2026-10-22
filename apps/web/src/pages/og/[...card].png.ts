import { readFile } from 'node:fs/promises';
import { createRequire } from 'node:module';
import type { APIRoute, GetStaticPaths } from 'astro';
import satori from 'satori';
import sharp from 'sharp';
import { OG_CARDS, ogCardKey, type OgCard } from '@/lib/seo/og';

/** Image Open Graph 1200 × 630 d'une page : direction « Charge », texte converti en tracés. */

const WIDTH = 1200;
const HEIGHT = 630;
const COLORS = {
  paper: '#f5f4ef',
  ink: '#0b1033',
  slate: '#575a6c',
  blue: '#00c2ff',
  /** Réservé au monogramme du logo. */
  cyan: '#00c2ff',
  lime: '#c8f031',
};

const require = createRequire(import.meta.url);
const font = (weight: 500 | 700 | 800) =>
  readFile(require.resolve(`@fontsource/manrope/files/manrope-latin-${weight}-normal.woff`));

let fonts: Promise<{ name: string; data: Buffer; weight: 500 | 700 | 800 }[]> | undefined;
const loadFonts = () =>
  (fonts ??= Promise.all(
    ([500, 700, 800] as const).map(async (weight) => ({
      name: 'Manrope',
      data: await font(weight),
      weight,
    })),
  ));

export const getStaticPaths = (() =>
  OG_CARDS.map((card) => ({
    params: { card: ogCardKey(card.path) },
    props: { card },
  }))) satisfies GetStaticPaths;

/** Arbre satori (équivalent JSX, sans dépendance au runtime React). */
type Node = { type: string; props: Record<string, unknown> & { children?: unknown } };
const h = (type: string, style: Record<string, unknown>, children?: unknown): Node => ({
  type,
  props: { style, children },
});

/** Monogramme « G » (components/ui/LogoMark.astro), en image pour satori. */
const LOGO_MARK = `data:image/svg+xml,${encodeURIComponent(
  `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><path d="M47.56 16.44A22 22 0 1 0 54 32" fill="none" stroke="${COLORS.ink}" stroke-width="11"/><path d="M31 26.5h16l8 11H31z" fill="${COLORS.cyan}"/></svg>`,
)}`;

function render({ eyebrow, title, meta }: OgCard): Node {
  const titleSize = title.length > 60 ? 54 : title.length > 32 ? 64 : 80;
  return h(
    'div',
    {
      position: 'relative',
      width: WIDTH,
      height: HEIGHT,
      boxSizing: 'border-box',
      display: 'flex',
      flexDirection: 'column',
      justifyContent: 'space-between',
      padding: '64px 72px',
      background: COLORS.paper,
      color: COLORS.ink,
      fontFamily: 'Manrope',
      overflow: 'hidden',
    },
    [
      // Aplats de marque, en retrait derrière le texte.
      h('div', {
        position: 'absolute',
        top: -120,
        right: -110,
        width: 420,
        height: 420,
        borderRadius: 420,
        background: COLORS.blue,
      }),
      h('div', {
        position: 'absolute',
        bottom: -90,
        right: 170,
        width: 220,
        height: 220,
        borderRadius: 220,
        background: COLORS.lime,
      }),
      h('div', { display: 'flex', alignItems: 'center', gap: 14 }, [
        { type: 'img', props: { src: LOGO_MARK, width: 44, height: 44 } },
        h('div', { display: 'flex', fontSize: 32, fontWeight: 800, letterSpacing: -1.3 }, [
          h('span', {}, 'genius'),
          h('span', { color: COLORS.blue }, '@'),
          h('span', {}, 'work'),
        ]),
      ]),
      h('div', { display: 'flex', flexDirection: 'column', gap: 26, maxWidth: 860 }, [
        h(
          'div',
          {
            display: 'flex',
            alignItems: 'center',
            gap: 14,
            fontSize: 21,
            fontWeight: 700,
            letterSpacing: 2.5,
            textTransform: 'uppercase',
            color: COLORS.slate,
          },
          [h('div', { width: 10, height: 10, borderRadius: 10, background: COLORS.blue }), eyebrow],
        ),
        h(
          'div',
          {
            display: 'flex',
            fontSize: titleSize,
            fontWeight: 800,
            lineHeight: 1.02,
            letterSpacing: -titleSize * 0.042,
          },
          title,
        ),
      ]),
      h(
        'div',
        {
          display: 'flex',
          alignItems: 'center',
          gap: 18,
          fontSize: 22,
          fontWeight: 600,
          color: COLORS.slate,
        },
        [
          h(
            'span',
            {
              background: COLORS.lime,
              color: COLORS.ink,
              padding: '6px 14px',
              borderRadius: 8,
              fontWeight: 800,
            },
            'genius-at-work.com',
          ),
          h('span', {}, meta ?? 'Antananarivo · Madagascar'),
        ],
      ),
    ],
  );
}

export const GET: APIRoute<{ card: OgCard }> = async ({ props }) => {
  const svg = await satori(render(props.card) as never, {
    width: WIDTH,
    height: HEIGHT,
    fonts: await loadFonts(),
  });
  const png = await sharp(Buffer.from(svg)).png({ compressionLevel: 9, palette: true }).toBuffer();
  return new Response(new Uint8Array(png), { headers: { 'Content-Type': 'image/png' } });
};
