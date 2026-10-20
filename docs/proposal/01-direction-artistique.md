# Proposition — Direction artistique & design system

> Statut : **direction A « Cyanotype » validée le 19 octobre 2026**. Implémentation : `packages/config/tailwind/tokens.css` et `apps/web/src/styles/`.
> Couvre les points 1 à 7, 13 et 14 de la demande. L'architecture technique est dans `02-architecture-technique.md`.
> Planche visuelle des 3 directions : voir l'artifact « GAW Directions » (lien dans la réponse).

---

## 0. Ce que la marque doit dire en 5 secondes

**« Une équipe d'ingénierie qui conçoit, construit et fait tourner de vrais produits — entre la France et Madagascar. »**

Faits réels disponibles pour le prouver (audit) : 15 ans d'expérience · plus de 20 personnes · 15 projets publiés · 4 produits portés en interne (Taxibrousse.mg, Chronolivraison, Emarket.mg, E-rdv — _à confirmer_) · une institution publique (DGI Madagascar) · une ONG internationale (Transparency International Initiative Madagascar) · deux bureaux (Claix/Grenoble, Antananarivo).

Le design doit **montrer** ces faits plutôt que les affirmer : diagrammes, métadonnées précises, captures en grand, chiffres réels, coordonnées des deux bureaux.

---

> **Garde-fou** : j'ai volontairement écarté les trois esthétiques devenues des clichés des sites générés par IA : crème + serif + terracotta, quasi-noir + vert acide, et dégradé violet → bleu. Chaque direction ci-dessous part d'un élément propre à Genius At Work.

## 1. Direction recommandée — A. « Cyanotype »

**Idée** : le _cyanotype_ est le procédé qui a donné son nom au _blueprint_, le tirage bleu de Prusse des plans d'ingénieur. C'est exactement ce que fait Genius At Work : concevoir des plans, puis les construire. La direction prolonge aussi les couleurs du logo actuel (**cyan** et **lilas**) au lieu de les jeter. On garde la continuité de marque, avec un niveau de finition supérieur.

| Axe                 | Choix                                                                                                                                                                                               |
| ------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Ambiance            | Bleu de Prusse profond comme **surface principale des chapitres forts**, blanc froid pour la lecture, cyan du logo en lumière, lilas du logo en accent rare                                         |
| Composition         | Grille 12 colonnes rendue visible en filets fins, comme sur un plan ; asymétrie assumée ; cartouches techniques (le bloc « titre / échelle / date » des plans) réinterprétés en en-têtes de section |
| Signature graphique | Le **@** de Genius@work devient un nœud de connexion dans les diagrammes, un loader, une puce ; tracés au trait blanc et cyan sur fond Prusse, cotes et repères d'angle autour des visuels          |
| Rythme              | Alternance de **chapitres clairs** (récit, projets, lecture) et de **chapitres Prusse** (hero, ingénierie, méthode, CTA final)                                                                      |
| Humain              | Ton direct à la première personne du pluriel, heure locale des deux bureaux en direct, annotations « à la main » en mono (`← fait à Antananarivo`)                                                  |
| Tech                | Métadonnées en mono (période · catégorie · stack), diagramme d'architecture interactif, détails de fiche technique                                                                                  |

**Pourquoi elle gagne** : c'est la seule direction qui tire son identité **de l'histoire de la marque** (logo cyan/lilas) **et de son métier** (le plan d'ingénieur). Elle coche _premium_ (retenue, typographie large, espace), _technique_ (traits, cotes, mono), _créatif_ (composition, scènes de motion) et _humain_ (ton, deux pays, annotations). Elle n'a pas besoin des photos d'équipe que nous n'avons pas encore, et elle se distingue nettement des sites d'agences actuels.

**Risques et parades**

- Le bleu peut devenir froid ou « corporate » → typographie large et expressive, lilas en contrepoint, ton éditorial chaleureux.
- Trop de diagrammes peuvent sembler austères → les captures projets en grand format, en couleurs, cassent le monochrome bleu.

---

## 2. Alternatives

### B. « Champs de couleur » — créatif, énergique, humain

- Chaque chapitre possède un **aplat saturé** : lagon `#0B6E6C`, lilas `#C9B8FF`, soleil `#FFC94A`, encre `#16131F`, blanc `#FBFBFA`. La couleur change au scroll, d'une section à l'autre.
- Typo : **Bricolage Grotesque** (display à axe optique, expressive) + **Onest** (texte).
- Effets : fondus de couleur liés au scroll, formes pleines découpées, gros chiffres, curseur coloré.
- ✅ Très mémorable, chaleureux, ancré dans la lumière de Madagascar, fort potentiel sur les réseaux sociaux.
- ❌ Moins rassurant pour les institutions et les DSI ; signal technique faible ; la gestion des contrastes de chaque aplat contraint fortement les captures projets ; vieillit plus vite.

### C. « Atelier Monochrome » — conseil haut de gamme

- Noir `#111111`, blanc `#F5F5F3`, gris `#5C5C5C` / `#9A9A9A`, **aucune couleur** sauf les captures projets.
- Typo : **Bodoni Moda** (didone à fort contraste, très grande) + **IBM Plex Mono** pour tout le reste.
- Effets : très grands blancs, typographie gigantesque, transitions en volets noirs, images en noir et blanc qui passent en couleur au survol.
- ✅ Très premium et sûr, plaît aux grands comptes, intemporel.
- ❌ Froid, peu « digital » ; la didone connote mode et luxe plus qu'ingénierie ; aucune trace de l'identité existante.

### Synthèse

| Critère                            | A. Cyanotype            | B. Champs de couleur | C. Atelier Monochrome |
| ---------------------------------- | ----------------------- | -------------------- | --------------------- |
| Premium                            | ●●●●                    | ●●●                  | ●●●●●                 |
| Technique                          | ●●●●●                   | ●●                   | ●●●                   |
| Créatif                            | ●●●●                    | ●●●●●                | ●●●                   |
| Humain                             | ●●●●                    | ●●●●●                | ●●                    |
| Lisibilité longue                  | ●●●●●                   | ●●●●                 | ●●●●●                 |
| Continuité avec la marque actuelle | **Forte** (cyan, lilas) | Moyenne (lilas)      | Nulle                 |
| Durabilité de l'esthétique         | ●●●●●                   | ●●●                  | ●●●●●                 |

---

## 3. Palette (direction A)

Toutes les paires texte/fond ci-dessous sont vérifiées **WCAG AA** (≥ 4.5:1).

### Couleurs de base

| Token           | Hex       | Usage                                                             |
| --------------- | --------- | ----------------------------------------------------------------- |
| `paper`         | `#F3F5F8` | Fond des chapitres clairs (blanc froid, légèrement bleuté)        |
| `paper-2`       | `#E6EBF2` | Fonds secondaires, champs de formulaire                           |
| `line`          | `#C9D2DE` | Filets, bordures, grille visible                                  |
| `prussian`      | `#0B1F3F` | Texte principal sur clair **et** surface des chapitres forts      |
| `prussian-2`    | `#12294F` | Surfaces surélevées sur Prusse                                    |
| `prussian-line` | `#24406E` | Filets et tracés secondaires sur Prusse                           |
| `slate`         | `#4A5A72` | Texte secondaire sur clair                                        |
| `mist`          | `#A9B8CF` | Texte secondaire sur Prusse                                       |
| `cobalt`        | `#1B4FD1` | Liens, focus, états actifs **sur clair**                          |
| `cyan`          | `#38B6FF` | Cyan du logo : tracés, accents, CTA **sur Prusse**                |
| `lilac`         | `#B9A7F2` | Lilas du logo : accent rare (≤ 5 %), annotations, détails humains |
| `success`       | `#1F7A4D` | Validation formulaire                                             |
| `danger`        | `#C42B2B` | Erreurs formulaire                                                |

### Contrastes vérifiés

| Paire                               | Ratio |
| ----------------------------------- | ----- |
| prussian / paper                    | 15.00 |
| slate / paper                       | 6.42  |
| cobalt / paper                      | 6.23  |
| cobalt / paper-2                    | 5.68  |
| blanc / cobalt (bouton)             | 6.81  |
| paper / prussian                    | 15.00 |
| cyan / prussian                     | 7.26  |
| mist / prussian                     | 8.15  |
| lilac / prussian                    | 7.70  |
| prussian / cyan (bouton sur Prusse) | 7.26  |

### Tokens sémantiques

Les composants ne consomment **jamais** les couleurs brutes, uniquement des rôles : `--bg`, `--bg-raised`, `--fg`, `--fg-muted`, `--border`, `--accent`, `--accent-fg`, `--focus`. Un chapitre fort = `data-surface="prussian"` sur la `<section>` : les rôles basculent, les composants restent identiques.

> **Logo** : je propose de garder le **monogramme GAW** et ses deux couleurs, mais de le redessiner en vectoriel, avec une version mono (prussian / paper) pour les petits formats. À valider : c'est une évolution de l'identité, pas une rupture.

---

## 4. Typographie (direction A)

| Rôle       | Police                                                                           | Raison                                                                                                                                     |
| ---------- | -------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------ |
| Display    | **Archivo** variable, axe de chasse **étendu** (`wdth` 112–125), graisse 600–800 | Grotesque industrielle d'origine, dont la version étendue rappelle le lettrage des cartouches de plans ; forte personnalité sans fantaisie |
| Texte & UI | **Archivo** chasse normale (`wdth` 100), 400–600                                 | Une seule famille variable : cohérence parfaite et un seul fichier à charger pour titres et texte                                          |
| Technique  | **Martian Mono** variable (axe de chasse)                                        | Mono à forte identité, ni « terminal » ni générique : cotes, métadonnées, numéros, annotations, code                                       |

Le contraste vient de la **chasse** (titres étendus, texte normal, annotations mono condensées), pas d'un mélange de familles. Exemple : « NOUS CONSTRUISONS DES PRODUITS QUI TIENNENT LA CHARGE. » en Archivo étendu 700, le chapeau en Archivo 400, `↳ 15 ans · France + Madagascar` en Martian Mono.

Toutes OFL et auto-hébergées (woff2 variables, sous-ensemble latin + latin-ext pour les accents et le malgache), `font-display: swap`, polices de repli ajustées (`size-adjust`) pour éviter le CLS. Préchargement d'Archivo uniquement.

### Échelle typographique fluide (320 → 1440 px)

| Token        | Taille (clamp)      | Interligne | Graisse                 | Usage                          |
| ------------ | ------------------- | ---------- | ----------------------- | ------------------------------ |
| `display-xl` | 3.25rem → 9rem      | 0.92       | 500, tracking −0.04em   | Hero                           |
| `display-l`  | 2.5rem → 6rem       | 0.95       | 500, −0.035em           | Titres de chapitre             |
| `h1`         | 2.25rem → 4.5rem    | 1.0        | 500, −0.03em            | Titre de page                  |
| `h2`         | 1.75rem → 3rem      | 1.05       | 500, −0.02em            | Sections                       |
| `h3`         | 1.25rem → 1.75rem   | 1.2        | 500                     | Sous-sections, cartes          |
| `lead`       | 1.125rem → 1.5rem   | 1.4        | 400                     | Chapeaux                       |
| `body`       | 1rem → 1.125rem     | 1.6        | 400                     | Texte courant (jamais < 16 px) |
| `small`      | 0.875rem            | 1.5        | 400                     | Légendes                       |
| `mono-label` | 0.75rem → 0.8125rem | 1.4        | 500, uppercase, +0.06em | Métadonnées, numéros           |

Longueur de ligne : 60–72 caractères pour le texte courant (`max-width: 68ch`).

---

## 5. Spacing & design tokens

### Espacements (base 4 px)

`space-1` 4 · `space-2` 8 · `space-3` 12 · `space-4` 16 · `space-6` 24 · `space-8` 32 · `space-12` 48 · `space-16` 64 · `space-24` 96 · `space-32` 128 · `space-48` 192

Espacements de section **fluides** : `section-y` = clamp(5rem, 12vw, 12rem) ; `section-gap` = clamp(3rem, 6vw, 6rem).

### Grille

| Breakpoint | Colonnes | Gouttière | Marge latérale |
| ---------- | -------- | --------- | -------------- |
| < 768 px   | 4        | 16 px     | 16 px          |
| 768 – 1023 | 8        | 24 px     | 32 px          |
| ≥ 1024     | 12       | 24 px     | 48 px          |
| ≥ 1440     | 12       | 32 px     | 64 px          |

Conteneur max : 1440 px (contenu) / 1680 px (pleine largeur éditoriale). Images « bleed » autorisées.

### Autres tokens

| Famille  | Valeurs                                                                                                                     |
| -------- | --------------------------------------------------------------------------------------------------------------------------- |
| Radius   | `none` 0 · `xs` 2 px (tags) · `sm` 6 px (inputs) · `md` 12 px (médias) · `pill` 999 px (boutons)                            |
| Bordures | 1 px `line` ; filets de grille 1 px à 60 % d'opacité                                                                        |
| Ombres   | Quasi absentes. `shadow-lift` (survol des médias uniquement) : `0 24px 48px -24px rgb(13 15 20 / .25)`                      |
| Z-index  | `base` 0 · `raised` 10 · `sticky` 100 · `nav` 200 · `overlay` 300 · `transition` 400 · `toast` 500                          |
| Durées   | `instant` 100 ms · `fast` 180 ms · `base` 300 ms · `slow` 600 ms · `reveal` 900 ms                                          |
| Easings  | `ease-out-expo` cubic-bezier(.16,1,.3,1) · `ease-in-out` cubic-bezier(.65,0,.35,1) · `ease-standard` cubic-bezier(.2,0,0,1) |

Implémentation : Tailwind v4 `@theme` dans `packages/config/tailwind/tokens.css`, partagé entre le site et (si besoin) l'admin.

---

## 6. Boutons & composants

### Boutons

| Variante               | Rendu                                                              | Interaction                                                                                                                             |
| ---------------------- | ------------------------------------------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------- |
| **Primary**            | Pilule `prussian` pleine, texte `paper`, flèche `→` dans un disque | Survol : le disque glisse vers la droite et la flèche se duplique (défilement) ; léger effet **magnétique** (souris uniquement, ≤ 6 px) |
| **Primary sur Prusse** | Pilule `cyan`, texte `prussian`                                    | Idem                                                                                                                                    |
| **Accent**             | Pilule `cobalt`, texte blanc                                       | Réservé au CTA de conversion final et à l'envoi du formulaire                                                                           |
| **Secondary**          | Texte + soulignement 1 px                                          | Le soulignement se retire à droite puis revient depuis la gauche                                                                        |
| **Ghost / icon**       | Cercle bordé 44×44 px                                              | Remplissage radial au survol                                                                                                            |

Hauteur minimale 48 px (cible tactile), focus = anneau `cobalt` (sur clair) ou `cyan` (sur Prusse) 2 px avec 3 px de décalage, état chargement = le libellé laisse place à un `@` qui tourne.

### Composants clés

- **Navigation** : barre fine transparente → fond `paper` flouté légèrement après 80 px de scroll (seul usage du flou) ; se masque au scroll descendant et revient au scroll montant. Logo · liens · sélecteur FR/EN · CTA « Parler de votre projet ». Mobile et tablette : bouton « Menu » → **overlay plein écran** sur fond `prussian`, liens en display-l numérotés, coordonnées des deux bureaux en bas.
- **Index de services** (pas de cartes) : liste pleine largeur, chaque ligne = `01` · titre · technos en mono. Au survol, la ligne s'ouvre et un aperçu visuel suit le curseur. Sur mobile, c'est un accordéon.
- **Carte projet éditoriale** : capture dans un cadre navigateur minimal, puis une ligne mono `2019—2022 · App Web · Zend, Cordova`, le titre et le résumé. Survol : l'image se recadre (scale 1.04) et le titre glisse. Tailles variées dans une grille asymétrique (7/5, 5/7, 12).
- **Tags** : mono uppercase, bordure 1 px, radius xs.
- **Formulaire** : labels visibles au-dessus, champs à fond `paper-2` sans bordure qui gagnent un filet `cobalt` au focus, erreurs sous le champ (icône + texte), récapitulatif d'erreurs en haut au submit, sélection du type de projet en **chips**.
- **Statistiques** : chiffres en display (compteur animé), légende mono, source implicite (uniquement des chiffres réels).
- **Diagramme système** (chapitre ingénierie) : SVG, couches Frontend → API → Backend → Data → Infra → CI/CD, reliées par des connecteurs `@`. Il se construit au scroll ; au survol d'une couche, les technos s'affichent.
- **Footer** : grand titre « Construisons quelque chose de solide. » en display, deux colonnes bureaux avec heure locale en direct, navigation, réseaux, langue, légal. Le monogramme est découpé en très grand en bas du footer.
- **Modal** (galerie projet uniquement) : `<dialog>` natif, focus piégé, fermeture avec Échap.

---

## 7. Stratégie d'animation GSAP

### Principes

1. **Le mouvement explique** : il révèle une hiérarchie, une construction ou une continuité, jamais pour décorer.
2. **Contenu visible sans JS** : le HTML est lisible et complet avant toute animation ; les états initiaux ne sont appliqués que si le JS et le mouvement sont autorisés (classe `motion-ok` posée en `<head>`, avec un garde-fou qui affiche tout au bout de 2,5 s).
3. **`prefers-reduced-motion`** : tout passe par `gsap.matchMedia()`. En mode réduit, pas de parallaxe, de pin, de scrub, de split ni de curseur ; seules restent des transitions d'opacité ≤ 200 ms. Le site reste complet.
4. **Performance** : uniquement `transform`, `opacity` et `clip-path` ; aucune animation de layout. GSAP est chargé en différé (`requestIdleCallback`) et les ScrollTrigger sont groupés.
5. **Animations isolées** : aucun code GSAP dans les composants métier (détail ci-dessous).

### Catalogue (presets réutilisables)

| Preset               | Description                                                                                     | Durée / easing                  |
| -------------------- | ----------------------------------------------------------------------------------------------- | ------------------------------- |
| `reveal-lines`       | Titres découpés en lignes (SplitText, masque), montée de 100 %                                  | 0.9 s · expo.out · stagger 0.08 |
| `reveal-words-scrub` | Manifeste : mots qui passent de 20 % à 100 % d'opacité au scroll                                | scrub                           |
| `reveal-up`          | Bloc : y 24 → 0, opacité 0 → 1                                                                  | 0.7 s · expo.out                |
| `reveal-image`       | Clip-path inset 100 % → 0 et scale 1.15 → 1                                                     | 1.1 s · expo.inOut              |
| `stagger-list`       | Listes et grilles                                                                               | 0.5 s · stagger 0.06            |
| `parallax`           | Translation y de ±8 % max, médias uniquement                                                    | scrub                           |
| `pin-horizontal`     | Galerie projets en scroll horizontal épinglé (desktop ≥ 1024 uniquement)                        | scrub                           |
| `draw-diagram`       | Tracé SVG des connecteurs du diagramme (stroke-dashoffset)                                      | scrub                           |
| `counter`            | Chiffres 0 → valeur                                                                             | 1.2 s · power2.out              |
| `magnetic`           | Boutons, pointeur fin uniquement                                                                | quickTo 0.4 s                   |
| `cursor`             | Curseur suiveur (point + label « Voir » sur les projets), pointeur fin uniquement, désactivable | quickTo                         |
| `marquee`            | Défilement des technos, en pause au survol et hors écran                                        | linéaire                        |

### Transitions de page

- **Astro `<ClientRouter />`** (View Transitions API) pour la navigation.
- Transition par défaut : rideau `prussian` tramé de la grille du plan qui monte en 0.45 s puis se retire, avec le titre de la page de destination en mono pendant le passage. L'animation de sortie est plus courte que celle d'entrée.
- **Projet → étude de cas** : élément partagé natif (`transition:name`) entre la vignette et le hero de l'étude de cas, ce qui donne une continuité visuelle forte sans Flip.
- Mode réduit : simple fondu de 150 ms.
- Cycle de vie : `astro:page-load` initialise les animations de la page, `astro:before-swap` détruit le `gsap.context()` courant (aucune fuite de ScrollTrigger).

### API déclarative

Les composants n'écrivent **que des attributs** :

```html
<h2 data-anim="reveal-lines">…</h2>
<figure data-anim="reveal-image" data-anim-delay="0.1">…</figure>
<section data-anim-scope>…</section>
```

Un unique contrôleur (`src/animations/controller.ts`) scanne les scopes, instancie les presets et les nettoie. Les animations complexes (hero, diagramme, galerie épinglée) sont des **scènes** dédiées (`src/animations/scenes/*.ts`) attachées par `data-scene="hero"`.

---

## 13. Stratégie responsive

- **Mobile-first**, breakpoints Tailwind en rem : `sm` 40 (640) · `md` 48 (768) · `lg` 64 (1024) · `xl` 80 (1280) · `2xl` 90 (1440) · `3xl` 120 (1920).
- **Typo et espacements fluides** (`clamp`) : pas de sauts brutaux entre breakpoints.
- **Container queries** pour les cartes et les blocs réutilisés (une carte projet s'adapte à sa colonne, pas à l'écran).
- **Vrais layouts mobiles**, pas une réduction du desktop :

| Élément             | Desktop                                       | Mobile                                                                  |
| ------------------- | --------------------------------------------- | ----------------------------------------------------------------------- |
| Hero                | Titre display + diagramme interactif à droite | Titre plein écran, diagramme simplifié en dessous                       |
| Galerie projets     | Scroll horizontal épinglé                     | Pile verticale, image pleine largeur                                    |
| Services            | Index interactif avec aperçu au curseur       | Accordéon                                                               |
| Méthode             | Timeline horizontale scrubbée                 | Stepper vertical avec ligne de progression                              |
| Diagramme stack     | Couches côte à côte                           | Couches empilées, dépliables                                            |
| Navigation          | Barre + CTA                                   | Bouton Menu → overlay plein écran, CTA fixe en bas sur les pages projet |
| Curseur, magnétisme | Actifs (pointeur fin)                         | Désactivés                                                              |

- Images : `srcset` + `sizes` exacts par layout, recadrages distincts pour mobile si nécessaire (via le hotspot Sanity).
- Tests systématiques à 320, 375, 390, 768, 1024, 1280, 1440 et 1920 px (Playwright, captures de référence), en portrait et en paysage pour les tablettes.

---

## 14. Stratégie d'accessibilité (WCAG 2.2 AA)

- **Structure** : landmarks (`header`, `nav`, `main`, `footer`), un seul H1 par page, hiérarchie Hn stricte, `lang="fr"` ou `"en"` sur `<html>` et `lang` local pour les noms en langue étrangère.
- **Clavier** : lien d'évitement « Aller au contenu », ordre de tabulation logique, menu overlay avec focus piégé + Échap + retour du focus au déclencheur, galerie épinglée entièrement navigable sans scroll (les liens restent dans le DOM).
- **Focus visible** partout : anneau `cobalt` (sur clair) ou `cyan` (sur Prusse) 2 px + décalage, jamais supprimé.
- **Contrastes** : tokens vérifiés (§3) ; texte sur image toujours posé sur un voile calculé.
- **Cibles** ≥ 44×44 px (au-delà du minimum de 24 px du critère 2.5.8).
- **Mouvement** : `prefers-reduced-motion` respecté (§7) ; aucun contenu clignotant ; marquee en pause au survol et au focus ; aucune information portée uniquement par l'animation.
- **SplitText** : texte original conservé pour les lecteurs d'écran (`aria-label` sur le conteneur, fragments en `aria-hidden`).
- **Formulaire** : labels visibles, `autocomplete` correct, erreurs liées via `aria-describedby`, récapitulatif focusable au submit, succès annoncé via `aria-live="polite"`, pas de CAPTCHA visuel (Turnstile en mode invisible avec repli accessible).
- **Médias** : `alt` descriptifs (FR/EN dans le CMS, champ obligatoire), images décoratives `alt=""`.
- **Langue** : le sélecteur indique la langue cible dans sa propre langue (« English » / « Français ») avec `hreflang` et `lang`.
- **Tests** : axe-core dans Playwright sur toutes les routes (bloquant en CI), Lighthouse ≥ 95, tests manuels VoiceOver (macOS/iOS) et NVDA avant la mise en ligne.
