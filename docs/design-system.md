# Design system « Sous charge »

Référence visuelle du site. Elle remplace la direction « Signal » décrite dans
`docs/proposal/03-refonte-visuelle.md` (conservé pour l'historique).

- Tokens : `packages/config/tailwind/tokens.css`
- Styles composés : `apps/web/src/styles/` (typographie, base, mouvement)
- Démonstration vivante : `/design-system` (non indexée), avec les contrastes calculés au build

## Idée

Le site montre un système qui tient la charge : des couches, des flux, des signaux. Le hero
simule du trafic à travers la stack, la page IA trace une demande jusqu'à sa valeur métier. Tout
le reste est sobre pour laisser ces deux moments respirer.

## Couleurs

| Groupe    | Couleurs                                           | Usage                                                      |
| --------- | -------------------------------------------------- | ---------------------------------------------------------- |
| Neutres   | papier, sable, encre, nuit, ardoise, brume, lignes | fonds, texte, filets                                       |
| Signal    | bleu `#1F4BFF` (jour), cyan `#00C2FF` (nuit)       | action, état actif, numéros                                |
| Vives     | cyan `#00C2FF`, lilas `#A98BFF`, citron `#C8F031`  | aplats : captures de projets, pastilles, surligneur, appel |
| Vermillon | `#FF5A36` (nuit), `#C93410` (jour)                 | rare : nœud en charge, « refus »                           |

Les couleurs vives d'origine sont conservées :

- **surligneur citron** sous le mot clé d'un titre (`<span class="mark">`) ;
- **bloc citron** de l'appel du pied de page, avec son disque cyan ;
- **pastilles et traits** colorés en alternance (`markerTint`) dans la méthode, les atouts, les
  principes ;
- **aplats de projets** (`projectTint`) derrière chaque capture, dans la liste et les fiches ;
- **images de partage** (OG) aux mêmes couleurs.

Une couleur vive porte toujours de l'encre (texte ou icône) : encre sur cyan 9,4:1, sur lilas
7,3:1, sur citron 14,8:1. Elle n'est jamais utilisée en texte sur fond clair.

Contrastes (WCAG AA, texte normal) : tous les couples utilisés dépassent 4,5:1. Le plus juste est
le vermillon foncé sur papier (4,8:1), réservé aux icônes et aux mots isolés.

### Surfaces

Chaque section déclare sa surface, les composants suivent sans réglage propre :

- `base` : papier, texte encre, signal bleu ;
- `subtle` : sable, pour alterner ;
- `night` : nuit, texte clair, signal cyan. L'en-tête du site la reprend tant qu'il recouvre un
  hero de nuit (page IA). Le hero d'accueil reste toujours clair ; seul son halo suit l'heure
  d'Antananarivo.

## Typographie

Deux voix :

- **Manrope** pour tout ce qui se lit : titres extra-gras et serrés, texte en graisse normale.
- **JetBrains Mono** pour le « système » : surtitres (`eyebrow`), libellés (`type-label`),
  numéros, compteurs, horloge, étiquettes de technologies (`Tag`), données d'un projet.

| Style          | Taille max | Usage                              |
| -------------- | ---------- | ---------------------------------- |
| `type-display` | 58 px      | titre du hero (accueil, page IA)   |
| `type-h1`      | 48 px      | titre des pages intérieures        |
| `type-h2`      | 44 px      | titre de section                   |
| `type-h3`      | 21 px      | titre de bloc                      |
| `type-lead`    | 19 px      | chapeau                            |
| `type-label`   | 12 px      | intitulé « système » (chasse fixe) |
| `type-mono`    | 12 px      | donnée « système » (chasse fixe)   |

Mot clé d'un titre : `<span class="mark">`, souligné d'une bande citron qui se déroule à
l'arrivée.

## Repères

- Listes numérotées (méthode, atouts, principes, cas d'usage, capacités, chaîne IA) : filet en
  haut, numéro `01` en chasse fixe et couleur signal, titre, texte.
- Boutons : `accent` (signal, une seule action de conversion par écran), `primary`, `outline`,
  `secondary` (lien avec flèche).
- Liste des réalisations : une ligne par projet (numéro, titre, client, catégories, période) ;
  à la souris, la capture du projet survolé suit le curseur ; sans souris, elle est dans la ligne.
- Bloc d'appel du pied de page : aplat citron et disque cyan ; masqué sur une page qui se
  termine déjà par son propre appel ou formulaire (`footerCta={false}` : page IA, contact).

## Mouvement

1. **Une seule animation continue par écran** : le hero ou le schéma IA. Elle s'arrête hors de
   l'écran et quand l'onglet est masqué.
2. **Le reste joue une fois**, à l'entrée : `reveal-up`, `reveal-lines`, `stagger`,
   `reveal-image`, et `stack` (« couches » : chaque ligne glisse de sous la précédente ; services,
   ingénierie, capacités IA).
3. **Entre deux pages**, la nouvelle s'ouvre dans un « @ » qui grandit, puis un disque la
   révèle en entier (View Transitions natives, `motion.css`) ; l'en-tête reste fixe.
4. **Le défilement reste libre** : aucun épinglage ; la chaîne IA suit le défilement sans le
   retenir.
5. **Mouvement réduit** : état fixe complet (simulation figée, schéma entier, chaîne allumée,
   navigation sans transition).

Presets : `apps/web/src/animations/presets` ; scènes : `apps/web/src/animations/scenes`
(`load-field` pour le hero, `ai-trace`, `ai-chain`).
