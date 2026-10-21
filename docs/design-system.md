# Design system « Charge »

Référence visuelle du site. Elle remplace les directions « Signal »
(`docs/proposal/03-refonte-visuelle.md`, conservé pour l'historique) et « Sous charge ».

- Tokens : `packages/config/tailwind/tokens.css`
- Styles composés : `apps/web/src/styles/` (typographie, base, mouvement)
- Démonstration vivante : `/design-system` (non indexée), avec les contrastes calculés au build

## Idée

Un studio qui conçoit, construit et fait tourner des systèmes. Une base claire et éditoriale,
ponctuée de deux moments forts : un aplat bleu électrique et des sections navy. Le citron signe.

## Couleurs

| Rôle      | Couleur                                      | Usage                                                |
| --------- | -------------------------------------------- | ---------------------------------------------------- |
| Primary   | bleu électrique `#2A45FF`                    | action, liens, aplat « blue »                        |
| Signature | citron `#C8F031`                             | mot clé, chiffres, survols, action sur sombre, appel |
| Secondary | navy `#0B1033`                               | sections sombres, pied de page                       |
| Accent    | corail `#FF6A45` (sombre), `#B93A18` (clair) | rare : nœud en charge, « refus »                     |
| Neutres   | papier, sable, blanc, encre, ardoise, brume  | fond, surface, texte, texte secondaire, filets       |

Règles :

- **une seule couleur vive par écran**, en plus du bleu ;
- le citron porte toujours de l'encre (14,8:1) et n'est jamais du texte sur fond clair ;
- le cyan ne sert qu'au monogramme du logo.

Contrastes (WCAG AA, texte normal) : tous les couples utilisés dépassent 4,5:1. Les plus justes :
citron sur bleu (4,7:1, chiffres et mots clés en grand) et corail foncé sur sable (4,7:1).

### Surfaces

Chaque section déclare sa surface, les composants suivent sans réglage propre :

| Surface  | Fond   | Texte  | Action (bouton accent) | Mise en valeur |
| -------- | ------ | ------ | ---------------------- | -------------- |
| `base`   | papier | encre  | bleu, texte blanc      | bleu           |
| `subtle` | sable  | encre  | bleu, texte blanc      | bleu           |
| `night`  | navy   | papier | citron, texte encre    | citron         |
| `blue`   | bleu   | blanc  | citron, texte encre    | citron         |
| `lime`   | citron | encre  | encre, texte citron    | bleu           |

L'en-tête du site reprend `night` tant qu'il recouvre un hero sombre (page IA). Le hero d'accueil
reste toujours clair ; seul son halo suit l'heure d'Antananarivo.

## Typographie

Deux voix :

- **Manrope** pour tout ce qui se lit (et le logotype « genius@work ») : titres extra-gras et
  serrés, texte en graisse normale.
- **JetBrains Mono** pour le « système » : surtitres (`eyebrow`), libellés (`type-label`),
  numéros, compteurs, horloge, étiquettes de technologies (`Tag`), données d'un projet.

Les deux sont sous licence libre (OFL), installées par npm (`@fontsource-variable`).

| Style            | Taille max | Graisse | Usage                               |
| ---------------- | ---------- | ------- | ----------------------------------- |
| `type-display`   | 64 px      | 800     | titre du hero (accueil, page IA)    |
| `type-h1`        | 44 px      | 800     | titre des pages intérieures         |
| `type-h2`        | 34 px      | 700     | titre de section                    |
| `type-h3`        | 22 px      | 700     | titre de bloc                       |
| `type-statement` | 48 px      | 600     | moment éditorial, une fois par page |
| `type-lead`      | 19 px      | 400     | chapeau                             |
| `type-label`     | 12 px      | 500     | intitulé « système » (chasse fixe)  |
| `type-mono`      | 12 px      | 400     | donnée « système » (chasse fixe)    |

La hiérarchie vient du contraste (taille, graisse, couleur), pas de titres géants. Mot clé d'un
titre : `<span class="mark">`, souligné d'une bande citron qui se déroule à l'arrivée.

## Mise en page

- **Un seul conteneur** (88 rem, marges 20 → 48 px) : tous les bords gauches sont alignés.
- Grille de 12 colonnes, gouttière `--spacing-column` (16 → 32 px).
- Espace vertical d'une section : 80 → 144 px ; en-tête de section → contenu : 40 → 72 px.
- Rayons : 4, 8, 14, 22, 32 px et pilule. Ombres légères, teintées navy.

## Hero d'accueil

- Composition asymétrique : texte sur 7 colonnes, visuel sur 5.
- Titre en trois lignes : « Nous [verbe] / des produits numériques / qui tiennent la charge. »
  Le verbe, en couleur de mise en valeur, change sans faire bouger le reste (tous les verbes
  occupent la même case, à la largeur du plus long). Les lecteurs d'écran lisent le titre fixe.
- Visuel : quatre couches (Interface, API, Modèles, Données) sous leur discipline (Design,
  Technologie, IA, Ingénierie), traversées par un trafic simulé qui réagit au pointeur.
- Bandeau du bas : heure d'Antananarivo, nombre de métiers et de projets publiés (comptés, pas
  saisis), projet à la une.
- Textes (verbes, couches, bandeau) : back-office › Pages et textes › Accueil › En-tête.

## Accueil : rythme

| #   | Section       | Surface | Composition                                                          |
| --- | ------------- | ------- | -------------------------------------------------------------------- |
| —   | Hero          | clair   | asymétrique, verbe qui tourne, couches sous trafic simulé            |
| 01  | Réalisations  | clair   | étude de cas principale en grand, puis piste horizontale             |
| 02  | Services      | navy    | liste interactive : la ligne active s'ouvre, aperçu d'un vrai projet |
| 03  | Manifeste     | clair   | texte du studio, puis les chiffres réels                             |
| 04  | IA            | navy    | schéma de la chaîne IA                                               |
| 05  | Ingénierie    | clair   | bandeau des technologies qui glisse, puis une couche par ligne       |
| 06  | Méthode       | sable   | quatre grands numéros en contour, reliés par un filet tracé          |
| 07  | Pourquoi nous | clair   | titre fixe à gauche, six points numérotés sur deux colonnes          |
| —   | Appel final   | citron  | bloc arrondi, disque bleu                                            |

Les sections sont numérotées (`index` de `ChapterHeader`) dans l'ordre du parcours.

## Repères

- Listes numérotées (méthode, atouts, principes, cas d'usage, capacités, chaîne IA) : filet en
  haut, numéro `01` en chasse fixe et couleur de mise en valeur, titre, texte. Traits et pastilles
  en citron (`markerTint`).
- Boutons : `accent` (une seule action de conversion par écran), `primary`, `outline`,
  `secondary` (lien avec flèche).
- Liste des réalisations : une ligne par projet (numéro, titre, client, catégories, période) ;
  à la souris, la capture du projet survolé suit le curseur ; sans souris, elle est dans la ligne.
- Bloc d'appel du pied de page : surface citron et disque bleu ; masqué sur une page qui se
  termine déjà par son propre appel ou formulaire (`footerCta={false}` : page IA, contact).

## Mouvement

1. **Une seule scène continue par écran** : la simulation du hero ou le schéma IA. Dans le
   hero, seul le verbe du titre tourne en plus (concevoir, construire, déployer, maintenir),
   toutes les 2,8 s. Tout s'arrête hors de l'écran et quand l'onglet est masqué.
2. **Le reste joue une fois**, à l'entrée : `reveal-up`, `reveal-lines`, `stagger`,
   `reveal-image`, et `stack` (« couches » : chaque ligne glisse de sous la précédente ; services,
   ingénierie, capacités IA).
3. **Piste horizontale** (`horizontal`) : sur grand écran, la section est épinglée et la
   piste glisse au rythme du défilement, avec une barre de progression ; ailleurs, elle défile
   au doigt, aimantée sur chaque carte. **Bandeau** (`drift`) : décoratif
   (`aria-hidden`), il glisse latéralement avec le défilement. **Filet** (`draw-line`) : se trace
   au défilement (méthode).
4. **Entre deux pages**, la nouvelle s'ouvre dans un « @ » qui grandit, puis un disque la
   révèle en entier (View Transitions natives, `motion.css`) ; l'en-tête reste fixe.
5. **Le défilement reste libre** : aucun défilement « lissé » qui détourne la molette ; seule la
   piste des réalisations épingle sa section, le temps de la parcourir.
6. **Mouvement réduit** : état fixe complet (simulation figée, schéma entier, chaîne allumée,
   navigation sans transition).

Durées : 100, 160, 240, 480, 800 ms. Courbes : `--ease-standard` (interface), `--ease-out`
(entrées), `--ease-out-expo` (révélations).

Presets : `apps/web/src/animations/presets` ; scènes : `apps/web/src/animations/scenes`
(`load-field` pour le hero, `ai-trace`, `ai-chain`).
