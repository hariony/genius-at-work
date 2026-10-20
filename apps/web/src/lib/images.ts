import type { ContentImage } from '@/types/content';

/**
 * Props `src` (+ dimensions et format de repli pour une image distante) attendues par <Picture>.
 * Une image distante n'a pas de format connu d'Astro, qui se replierait sur un PNG lourd :
 * repli en JPEG, sauf pour un PNG (transparence possible).
 */
export const imageSource = (image: ContentImage) =>
  'format' in image
    ? { src: image }
    : {
        src: image.src,
        width: image.width,
        height: image.height,
        fallbackFormat: image.src.toLowerCase().endsWith('.png') ? 'png' : 'jpg',
      };
