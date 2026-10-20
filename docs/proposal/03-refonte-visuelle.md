# 03 — Refonte visuelle « Signal »

Application de `specs/refonte.md`. Architecture, fonctionnalités, contenus et données inchangés.

## Diagnostic de la version précédente

- Titres géants en capitales étendues (Archivo à 125 %), deux familles de police, labels mono « § ».
- Paragraphe du manifeste en taille de titre, sections trop hautes (12 rem).
- Portfolio en grille 3×3, défilement horizontal épinglé, animations permanentes (bus, compteurs, rideau).
- Nombreuses grilles de cartes à bordures.

## Design system

Source unique : `packages/config/tailwind/tokens.css`. Les composants n'utilisent que les rôles sémantiques
(`bg`, `bg-raised`, `fg`, `fg-muted`, `border`, `accent`, `spark`…), résolus par `data-surface`.

### Couleurs (direction « Signal »)

Base neutre et chaude, un bleu électrique pour l'action, trois aplats vifs pour le rythme.

| Rôle                  | Token              | Valeur                | Contraste                  |
| --------------------- | ------------------ | --------------------- | -------------------------- |
| Background            | paper              | `#F6F5F1`             | —                          |
| Fond alterné          | sand               | `#EDECE6`             | slate 5,4                  |
| Text                  | ink                | `#0A0B1E`             | 17,8 sur paper             |
| Muted                 | slate              | `#5B5E70`             | 5,9 sur paper              |
| Border                | line / line-strong | `#E2E0D9` / `#CBC8BE` | décoratif                  |
| Primary               | blue               | `#1F4BFF`             | 5,5 sur paper, blanc 6,0   |
| Fond sombre           | ink                | `#0A0B1E`             | texte paper 17,8, mist 8,1 |
| Primary sur sombre    | cyan               | `#00C2FF`             | 9,4 sur ink                |
| Aplat                 | lilac              | `#A98BFF`             | ink dessus 7,3             |
| Surligneur, étincelle | lime               | `#C8F031`             | ink dessus 14,8            |
| Aplat clair           | blue-soft          | `#E6ECFF`             | ink dessus 16,5            |

Surfaces : `base` (paper), `subtle` (sand), `dark` (ink, accent cyan, étincelle lime).
Les aplats (cyan, lilas, lime, blue-soft) ne portent que du texte ink.
Teinte des cartes projet attribuée par `projectTint()` (`src/lib/brand.ts`).

### Logo

`LogoMark.astro` : arc « G » ouvert en `currentColor` et bloc cyan qui en sort, comme un signal.
`Wordmark.astro` : la marque + « genius@work » en minuscules extra-grasses, `@` en couleur d'accent.
Favicon SVG (variante sombre via `prefers-color-scheme`), PNG 96 px et `apple-touch-icon` 180 px.

### Typographie

Une seule famille : **Manrope** variable (testée face à Plus Jakarta Sans, Geist et DM Sans).

| Utilitaire               | Taille max               | Usage                   |
| ------------------------ | ------------------------ | ----------------------- |
| `type-display`           | 68 px, extra-gras        | titre du hero d'accueil |
| `type-h1`                | 56 px, extra-gras        | titre de page           |
| `type-h2`                | 44 px                    | titre de section        |
| `type-h3`                | 21 px                    | titre de bloc           |
| `type-lead`              | —                        | chapeau                 |
| `type-label` / `eyebrow` | 12 px, capitales, +0,1em | labels                  |

Tailles fluides de 320 à 1440 px, corps 16 px / 1,65.

### Espacements, conteneurs, grille

- Section `clamp(4.5rem → 8rem)`, écart interne `clamp(2.5rem → 4rem)`, gouttière `clamp(1.25rem → 3rem)`.
- Conteneurs : content 80 rem, wide 90 rem (portfolio), prose 42 rem.
- Grille 12 colonnes ; portfolio asymétrique 7/5 avec décalage vertical.

### Rayons, bordures, ombres

- Rayons : xs 4, sm 8, md 12, lg 20, xl 28, pill.
- Bordures 1 px `line`, `line-strong` au survol.
- Ombres xs, sm, md, très diffuses ; lg au survol des cartes projet.

### Composants

- **Boutons** : pilules `primary` (encre), `accent` (bleu, conversion), `outline`, `secondary` (lien souligné). Flèche décalée au survol.
- **Champs** : `.field`, fond blanc, anneau bleu au focus, `aria-invalid` en rouge. Puces `.chip`.
- **Cartes projet** : scène en aplat coloré, mockup navigateur, titre en lien étiré, méta (catégories, période) ; légère rotation au survol.
- **Surligneur** `.mark` : bande lime sous la moitié basse du mot clé des titres.
- **Pied de page** : bloc d'appel lime avec cercle cyan.

### Mouvement

- Presets : `reveal-up`, `reveal-lines`, `reveal-image` (clip-path), `stagger`, `parallax` (±4 %).
- Une seule scène : `hero-collage` (formes cyan et lilas, captures de deux projets publiés, badge), jouée une fois, sans boucle.
- Transitions de page : View Transitions natives du navigateur (`@view-transition`).
- `prefers-reduced-motion` : tout est désactivé, le contenu reste visible.

Référence vivante : `/design-system/` (noindex).
