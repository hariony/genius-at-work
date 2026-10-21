import sharp from 'sharp';
import { getProjects } from '@/services/content';
import type { ContentImage, Project } from '@/types/content';

/**
 * Teinte de chaque projet, tirée de sa capture au build : la couleur vive la plus présente
 * (les gris, blancs et noirs sont ignorés), ramenée à une luminosité et une saturation qui
 * tiennent un aplat. Texte posé dessus : encre ou papier, selon le meilleur contraste.
 * Sans couleur vive (capture en noir et blanc) ou si l'image est illisible : bleu de marque.
 * Une teinte imposée dans le back-office passe avant la capture ; elle n'est retouchée (plus
 * claire ou plus foncée, même couleur) que si le texte n'y atteint pas 7:1.
 */
export interface Tint {
  bg: string;
  fg: string;
}

const INK = '#0b1033';
const PAPER = '#f5f4ef';
const FALLBACK: Tint = { bg: '#1d34d6', fg: PAPER };
const HUE_BUCKETS = 24;
const MIN_CONTRAST = 7;

type Rgb = [number, number, number];

function toHsl([r, g, b]: Rgb): [number, number, number] {
  const [rn, gn, bn] = [r / 255, g / 255, b / 255];
  const max = Math.max(rn, gn, bn);
  const min = Math.min(rn, gn, bn);
  const l = (max + min) / 2;
  const d = max - min;
  if (d === 0) return [0, 0, l];
  const s = d / (1 - Math.abs(2 * l - 1));
  const h =
    max === rn ? ((gn - bn) / d + 6) % 6 : max === gn ? (bn - rn) / d + 2 : (rn - gn) / d + 4;
  return [h * 60, s, l];
}

function toHex(h: number, s: number, l: number): string {
  const a = s * Math.min(l, 1 - l);
  const channel = (n: number) => {
    const k = (n + h / 30) % 12;
    const value = l - a * Math.max(-1, Math.min(k - 3, 9 - k, 1));
    return Math.round(value * 255)
      .toString(16)
      .padStart(2, '0');
  };
  return `#${channel(0)}${channel(8)}${channel(4)}`;
}

function luminance(hex: string): number {
  const [r, g, b] = [1, 3, 5].map((i) => {
    const c = parseInt(hex.slice(i, i + 2), 16) / 255;
    return c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4;
  }) as Rgb;
  return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

const contrast = (a: string, b: string) => {
  const [hi, lo] = [luminance(a), luminance(b)].sort((x, y) => y - x) as [number, number];
  return (hi + 0.05) / (lo + 0.05);
};

async function readTint(image: ContentImage): Promise<Tint> {
  // Image locale (importée) : pas de capture du back-office à lire.
  if ('format' in image) return FALLBACK;
  const response = await fetch(image.src);
  if (!response.ok) return FALLBACK;
  const { data, info } = await sharp(Buffer.from(await response.arrayBuffer()))
    .resize(64, 64, { fit: 'inside' })
    .removeAlpha()
    .raw()
    .toBuffer({ resolveWithObject: true });

  // Pixels vifs regroupés par teinte, pondérés par le carré de leur saturation : la couleur de
  // marque (souvent un accent sur fond clair) l'emporte sur les grands aplats ternes.
  const buckets = Array.from({ length: HUE_BUCKETS }, () => ({ weight: 0, h: 0, s: 0, l: 0 }));
  for (let i = 0; i < data.length; i += info.channels) {
    const [h, s, l] = toHsl([data[i] ?? 0, data[i + 1] ?? 0, data[i + 2] ?? 0]);
    if (s < 0.3 || l < 0.12 || l > 0.9) continue;
    const bucket = buckets[Math.floor(h / (360 / HUE_BUCKETS)) % HUE_BUCKETS];
    if (!bucket) continue;
    const w = s * s;
    bucket.weight += w;
    bucket.h += h * w;
    bucket.s += s * w;
    bucket.l += l * w;
  }
  const best = buckets.reduce((a, b) => (b.weight > a.weight ? b : a));
  // Moins d'environ 1 % de l'image en couleur vive : pas de teinte propre.
  if (best.weight < data.length / info.channels / 150) return FALLBACK;

  const h = best.h / best.weight;
  const s = Math.max(0.5, Math.min(0.9, best.s / best.weight));
  const l = Math.max(0.32, Math.min(0.58, best.l / best.weight));
  return readable(h, s, l);
}

const pick = (bg: string) => (contrast(bg, INK) >= contrast(bg, PAPER) ? INK : PAPER);

/** Texte lisible dessus (y compris en atténué) : on fonce ou on éclaircit jusqu'à 7:1. */
function readable(h: number, s: number, l: number): Tint {
  let bg = toHex(h, s, l);
  const lighten = pick(bg) === INK;
  while (contrast(bg, pick(bg)) < MIN_CONTRAST && l > 0.15 && l < 0.85) {
    l += lighten ? 0.02 : -0.02;
    bg = toHex(h, s, l);
  }
  return { bg, fg: pick(bg) };
}

/** Teinte imposée : gardée telle quelle si elle est déjà assez contrastée. */
function fixedTint(hex: string): Tint {
  if (contrast(hex, pick(hex)) >= MIN_CONTRAST) return { bg: hex, fg: pick(hex) };
  const [r, g, b] = [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16)) as Rgb;
  return readable(...toHsl([r, g, b]));
}

const TINTS = new Map<string, Tint>(
  await Promise.all(
    getProjects().map(
      async (project) =>
        [
          project.slug,
          project.tint
            ? fixedTint(project.tint)
            : await readTint(project.cover).catch(() => FALLBACK),
        ] as const,
    ),
  ),
);

export const projectTint = (project: Project): Tint => TINTS.get(project.slug) ?? FALLBACK;

/** Variables CSS d'un aplat de projet, à poser avec la classe `project-tint`. */
export const tintStyle = (project: Project): string => {
  const { bg, fg } = projectTint(project);
  return `--tint:${bg};--tint-fg:${fg}`;
};
