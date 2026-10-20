# Audit de contenu — genius-at-work.com

> Phase 1 & 2 du brief (`specs/prompt.md`). Crawl effectué le **19 octobre 2026**.
> Source de vérité pour la refonte. Rien ici n'est inventé : chaque donnée provient du site actuel.
> Données brutes : `docs/audit-raw/` (texte extrait de chaque page + réponses REST WordPress).
> Assets récupérés : `assets/legacy/`.

---

## 1. Méthode

| Source                                                           | Résultat                                                  |
| ---------------------------------------------------------------- | --------------------------------------------------------- |
| `https://genius-at-work.com/` et `/en/`                          | HTML complet récupéré                                     |
| `sitemap.xml` (généré manuellement, mai 2024) + `wp-sitemap.xml` | 75 URLs recensées                                         |
| API REST WordPress (`/wp-json/wp/v2/…`)                          | `services`, `technos`, `team`, `pages`, `posts` (FR + EN) |
| Pages taxonomies (`portfolios-categories`, `portfolios-stacks`)  | Mapping projets ↔ catégories ↔ technos                    |

Stack actuelle : WordPress 7.1.3, WPML 4.6.10 (FR/EN), thème custom « genius » dérivé du template ThemeForest _Doob / Trydo_ (rainbowit), Contact Form 7 + hCaptcha, jQuery + Bootstrap + ~25 scripts vendor.

---

## 2. Pages existantes

### 2.1 Pages réellement utiles

| Page                 | FR                               | EN                                  | État                                                                |
| -------------------- | -------------------------------- | ----------------------------------- | ------------------------------------------------------------------- |
| Accueil (one-page)   | `/`                              | `/en/`                              | ✅ Contenu principal du site                                        |
| Liste réalisations   | `/portfolios/`                   | `/en/portfolios/`                   | ✅ 14 projets FR, 15 EN                                             |
| Détail projet        | `/portfolios/{slug}/`            | `/en/portfolios/{slug}/`            | ✅ sauf SQVF (erreur 500)                                           |
| Catégories projets   | `/portfolios-categories/{slug}/` | `/en/portfolios-categories/{slug}/` | ⚠️ Listes EN incomplètes/incohérentes                               |
| Stacks projets       | `/portfolios-stacks/{slug}/`     | `/en/portfolios-stacks/zend-en/`    | ⚠️ Archives techniques sans valeur éditoriale                       |
| Termes et conditions | `/termes-et-conditions/`         | `/en/terms-and-conditions/`         | ❌ **Lorem ipsum du template** (« Welcome to Doob Privacy Policy ») |

### 2.2 Pages vides ou parasites (à ne pas migrer)

| Page                                                                       | Problème                                                 |
| -------------------------------------------------------------------------- | -------------------------------------------------------- |
| `/services/{slug}/` ×6 (FR + EN)                                           | Titre + date uniquement, **aucun contenu**               |
| `/technos/{slug}/` ×8 (FR + EN)                                            | Titre + date uniquement, **aucun contenu**               |
| `/team/{slug}/` ×3 (FR + EN)                                               | Nom uniquement, **aucun rôle, aucune bio, aucune photo** |
| `/bonjour-tout-le-monde/`                                                  | Article WordPress par défaut                             |
| `/author/admin/`, `/category/non-classifiee/`, `/home-fr/`, `/en/home-en/` | Archives / doublons techniques                           |
| `/feed/`, `/xmlrpc.php`, `/wp-json/`                                       | Exposés publiquement                                     |

### 2.3 Navigation actuelle

- FR : Home · Services · Réalisations · Technologies · Contact
- EN : Home · Services · Projects · Stacks · Contact
- Tous les items sauf « Réalisations » sont des ancres de la homepage.

---

## 3. Contenu actuel — Homepage

### 3.1 FR

**Title** : `Genius at work Services | Agence de création de site internet` (identique en EN ⚠️)
**Meta description** : « Création site web à Madagascar, site internet , maintenance, référencement, wordpress et prestashop, hébergement, application mobile. »
**Meta keywords** : Genius at work, ssii, Madagascar, agence web, web agency, conseil, intégration, infogérance, exploitation, hébergement, TMA, développement internet, responsive web design, dotnet, .NET, java, PHP, cms, portail, réseau social d'entreprise, application mobile, forfait, assistance technique, régie, expertise, e-commerce

**Hero**

- Surtitre : AGENCE WEB EXPERT EN INNOVATION DIGITALE
- H1 : Genius@work
- Accroche : Des solutions sur mesure pour propulser votre entreprise
- Points : « Une équipe de génies talentueux encadrés par des experts » · « Une expérience forte de 15 ans en perpétuelle évolution »
- CTA : « Votre devis gratuit » · « Contactez-nous »
- Visuel : `slide.png` (photo d'open-space, probablement stock)

**Intro**

- H3 : Une équipe nouvelle génération
- « Les maître-mots sont la performance et la confiance. »
- « GENIUS est une équipe de plus de 20 personnes qui est à la pointe de toutes les dernières bonnes pratiques des STACKS TECHNIQUES pour pousser leurs limites jour après jour. »

**Processus** — Surtitre « Timeline » · H2 « Processus de développement Scrum »

1. **Empathie** — Écouter et accueillir vos idées, échange d'expérience
2. **Expertise** — Analyse de vos besoins par nos talents
3. **Solution** — Développement de nos stratégies, inventivité
4. **Développement** — Application du modèle Scrum au cours du cycle de développement

**Services** — Surtitre « Ce Qu'on Peut Faire Pour Vous » · H2 « Nos talents aux services de projets ambitieux » · « L'agence web 360° qui vous accompagne vers votre réussite ! » (6 services, voir §4)

**Projets** — H2 « Projets » · « Des projets pharaoniques qui ont déjà prouvé notre expertise » · Filtres : All, App Web, Design UX/UI, E-commerce, Innovation, Site vitrine, Voyage · 6 projets mis en avant · CTA « Toutes les réalisations »

**Technologies** — Surtitre « Nous utilisons les technologies les plus demandées » · H2 « Nos stacks techniques » · 8 logos

**Contact** — Surtitre « Formulaire de contact » · H2 « Notre adresse de contact ici. » (placeholder non remplacé ⚠️) · coordonnées (§7) · formulaire (§7.3)

**Footer** — Facebook, LinkedIn, « © Genius at work Services 2026 »

**Bloc CTA des pages projet** — « Prêt à discuter un projet avec nous ? » · « Vous avez des besoins spécifiques ? Nous avons des solutions techniques ! » · « Contactez-nous » (non traduit sur les pages EN ⚠️)

**Liste des réalisations** — Surtitre « C'est parti » · H2 « Appréciez nos réalisations récentes! »

### 3.2 EN

**Hero** — WEB AGENCY EXPERT IN DIGITAL INNOVATION · Genius@work · Tailored solutions to propel your business forward · A team of talented geniuses supervised by experts · A strong experience of 15 years in perpetual evolution · CTA « Your free quote » / « Contact us »

**Intro** — A new generation team · The watchwords are performance and trust. · GENIUS is a team of over 20 people who are at the forefront of all the latest best practices in TECHNICAL STACKS to push their limits day after day.

**Process** — Timeline · Scrum development process

1. **Empathy** — Listening to and welcoming your ideas, exchange of experience
2. **Expertise** — Analysis of your needs by our talents
3. **Solution** — Development of our strategies, inventiveness
4. **Development** — Application of the scrum model during the development cycle

**Services** — What we can do for you · Our talents at the service of ambitious projects · The 360° web agency that accompanies you to your success!

**Projects** — Pharaonic projects that have already proven our expertise · Filters: All, Design UX/UI, E-commerce, Innovation, Showcase site, Travel, Web app · CTA « All our achievements »

**Technologies** — We use the most requested technologies · Our technical stacks

**Contact** — Contact form · Our contact address here. · Quick contact · French office · Madagascar Office

**Pages projet EN** — Les libellés « Date de début », « Date de fin », « Client », « Catégories », « Voir le projet », « Détails du projet » et le bloc CTA restent **en français** ⚠️

---

## 4. Services

Contenu disponible uniquement sur la homepage (les pages détail sont vides).

| #   | FR                                                                                                                                                                                                                | EN                                                                                                                                                                                        | Slug FR / EN                                                                 |
| --- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------- |
| 01  | **Création de site internet** — Des sites internet captivants et fonctionnels, conçus sur mesure pour votre entreprise. Nous combinons design innovant et convivialité pour vous démarquer dans le monde digital. | **Website creation** — Engaging and functional websites, tailored to your business. We blend innovative design and user-friendliness to set you apart in the digital world.               | `creation-de-site-internet` / `website-creation`                             |
| 02  | **Application mobile** — Créez des expériences mobiles captivantes avec nos applications sur mesure. Fonctionnalités avancées, design élégant et convivialité pour répondre à vos besoins spécifiques.            | **Mobile Application** — Craft captivating mobile experiences with our custom applications. Advanced features, elegant design, and user-friendliness tailored to your specific needs.     | `application-mobile` / `mobile-application`                                  |
| 03  | **Application web** — Simplifiez votre workflow avec nos applications web intuitives. Elles offrent une interface conviviale et des fonctionnalités puissantes pour une productivité accrue.                      | **Web application** — Simplify your workflow with our intuitive web applications. They provide a user-friendly interface and powerful features for increased productivity.                | `application-web` / `web-application`                                        |
| 04  | **Solution logiciel entreprise** — Développez votre entreprise avec notre solution logicielle adaptée à vos besoins. Personnalisable, évolutive et sécurisée pour répondre aux défis de votre activité.           | **Business software solution** — Grow your business with our software solution tailored to your needs. Customizable, scalable, and secure to meet the challenges of your operations.      | `solution-logiciel-entreprise` / `business-software-solution`                |
| 05  | **Solution E-Commerce** — Notre solution e-commerce offre une plateforme robuste pour vendre en ligne. Personnalisable, sécurisée et facile à gérer pour maximiser vos opportunités de vente.                     | **E-Commerce solution** — Our e-commerce solution provides a robust platform for selling online. Customizable, secure, and easy to manage, maximizing your sales opportunities.           | `solution-e-commerce` / `e-commerce-solution`                                |
| 06  | **Maintenance, évolution et hébergement** — Assurez la stabilité et la sécurité de votre site avec notre service de maintenance et d'hébergement. Évoluez en toute confiance grâce à notre assistance dédiée.     | **Maintenance, evolution and hosting** — Ensure the stability and security of your site with our maintenance and hosting service. Evolve with confidence thanks to our dedicated support. | `maintenance-evolution-et-hebergement` / `maintenance-evolution-and-hosting` |

**Mentionnés uniquement dans la meta description / keywords FR** (pas de contenu associé) : référencement, conseil, intégration, infogérance, TMA, assistance technique, régie, .NET, Java. → À **ne pas** promouvoir comme services sans validation du client.

**Liens service ↔ projets** (déduits des catégories/technos réelles, utiles pour la refonte) :

- Site vitrine → Dialopro, Miarakap, Transparency International, Chronolivraison
- Mobile → Taxibrousse.mg, Chronolivraison (Cordova + Framework7)
- Application web → SQVF/DGI, E-rdv, Euromputer, Taxibrousse.mg, Chronolivraison, Transparency International
- E-commerce → Alter Smoke, Fashionshopgroup, Emarket.mg, Euromputer, Taxibrousse.mg, Chronolivraison, Wellcoloc
- Logiciel entreprise → SQVF/DGI (outil de gestion interne de la DGI)

---

## 5. Projets

### 5.1 Inventaire

15 projets. Ordre d'affichage actuel conservé. ★ = mis en avant sur la homepage.

| #   | Projet                               | Slug FR / EN                                                 | Client                                           | Période                                        | Lien                                           |
| --- | ------------------------------------ | ------------------------------------------------------------ | ------------------------------------------------ | ---------------------------------------------- | ---------------------------------------------- |
| ★1  | MagicStay                            | `magicstay`                                                  | Magistay _(sic — probablement « MagicStay »)_    | 15/07/2022 → 22/07/2022 (FR) · 27/07/2022 (EN) | fr.magicstay.com (FR) · www.magicstay.com (EN) |
| ★2  | Transparency International           | `transparency-internationale` / `transparency-international` | Transparency International Initiative Madagascar | 08/05/2019 → 11/10/2019                        | transparency.mg                                |
| ★3  | SQVF — Direction Générale des Impôts | `sqvf-direction-generale-des-impots`                         | DGI Madagascar _(EN uniquement)_                 | 15/05/2019 → 17/11/2022 _(EN uniquement)_      | — (page en erreur 500)                         |
| ★4  | Taxibrousse.mg                       | `taxibrousse-mg`                                             | Genius _(produit interne)_                       | 09/05/2019 → 09/11/2022                        | www.taxibrousse.mg                             |
| ★5  | Wellcoloc                            | `wellcoloc`                                                  | —                                                | —                                              | wellcoloc.com                                  |
| ★6  | E-rdv                                | `e-rdv`                                                      | Genius _(EN uniquement)_                         | 10/05/2019 → 16/06/2021 _(EN uniquement)_      | e-rdv.mg (http)                                |
| 7   | DFC Com                              | `dfc-com`                                                    | Pascal Daniau                                    | 05/05/2020 → 21/10/2022                        | www.dfccom.com                                 |
| 8   | Fashionshopgroup                     | `fashionshopgroup`                                           | François Kryvenko                                | 10/10/2019 → 07/02/2020                        | fashionshopgroup.com/fr                        |
| 9   | Alter Smoke                          | `alter-smoke`                                                | Alter Smoke                                      | 02/11/2012 → 16/01/2013                        | www.altersmoke.com/fr                          |
| 10  | Chronolivraison                      | `chronolivraison`                                            | Genius _(produit interne)_                       | 05/06/2020 → 21/11/2020                        | chronolivraison.mg                             |
| 11  | Euromputer _(sic)_                   | `euromputer`                                                 | Eurcomputer _(sic)_                              | 09/03/2016 → 21/09/2018                        | eurocomputer.fr                                |
| 12  | Emarket.mg                           | `emarket-mg`                                                 | Genius _(produit interne)_                       | 02/04/2020 → 17/06/2021                        | emarket.mg                                     |
| 13  | Dialopro                             | `dialopro`                                                   | Dialopro                                         | 12/05/2020 → 19/06/2020                        | dialopro.com                                   |
| 14  | Miarakap                             | `miarakap`                                                   | MIARAKAP                                         | 11/07/2019 → 27/11/2019                        | miarakap.com                                   |
| 15  | Evasommeil                           | `evasommeil` _(EN uniquement — la page FR redirige vers EN)_ | Antoine Le Mosquet                               | 09/10/2020 → 18/03/2021                        | www.evasommeil.com                             |

> **Constat important** : Taxibrousse.mg, Chronolivraison, Emarket.mg et E-rdv ont pour client « Genius ». Ce sont des **produits conçus et opérés par Genius At Work**. C'est un argument fort pour la refonte (« nous construisons et exploitons nos propres produits ») — à confirmer avec le client avant de l'écrire ainsi.

> **Données personnelles** : les noms de personnes physiques (Pascal Daniau, François Kryvenko, Antoine Le Mosquet, « Valéry ») sont affichés comme clients. À valider avant republication.

### 5.2 Descriptions (FR / EN)

**MagicStay**

- Résumé FR : Location de vacances, location courte durée, location saisonnière et appart hotel
- Résumé EN : Vacation rental, short-term rental, seasonal rental and apartment hotel
- Détail FR : _MagicStay, le meilleur de l'hébergement pour vos voyages d'affaires ou la mobilité professionnelle._ L'aventure a commencé en 2013. Valéry travaillait alors dans un groupe organisateur de nombreux salons internationaux. Il voyageait beaucoup, seul ou avec son équipe, et n'avait pas le droit de réserver des appartements car ils n'étaient pas référencés dans le SBT (Self Booking Tool) utilisé par son entreprise. Les appartements ne répondaient pas aux enjeux de sécurité et ne pouvaient pas être réservés en réservation immédiate. Pourtant Valéry utilisait ce type d'hébergement durant ses vacances et appréciait l'espace, l'indépendance et le « vivre comme à la maison ».
- Détail EN : _MagicStay, the best accommodation for your business trips or professional mobility._ The adventure began in 2013. At that time Valéry was working in an event management group which organized many international trade shows. He travelled a lot, alone and with his team, and was not allowed to book apartments because they were not referenced in the SBT (Self Booking Tool) used by his company. The apartments did not meet security requirements and could not be booked immediately. However, Valery used this type of accommodation during his holidays and appreciated the space, independence and feeling of being at home.
- → Seul projet avec un vrai « contexte » rédigé. Utilisable pour la section _Context_ de la page projet.

**Transparency International**

- FR : Amélioration du site Transparency Internationale Initiative Madagascar
- EN : Improvement of the Transparency International Initiative Madagascar website
- Note : le nom officiel est « Transparency International » ; « Internationale » (FR) est une coquille.

**SQVF — Direction Générale des Impôts**

- FR : Outils de gestion centralisée et suivi des contrôles fiscaux au sein de la DGI
- EN : Centralized management tools and monitoring of tax audits within the DGI
- Détail : **non récupérable** (erreur PHP critique sur les 2 langues). Le sigle SQVF n'est développé nulle part.

**Taxibrousse.mg**

- Résumé FR : Application mobile et siteweb de réservation en ligne de billets pour les taxibrousses
- Détail FR : Application et site web de réservation en ligne avec choix de places et paiement sécurisé (Mobile money et carte bancaire) pour les Taxi-brousses et transport première classe à Madagascar
- Résumé EN : Mobile application and website for online ticket booking for taxibrousses
- Détail EN : Online booking application and website with choice of seats and secure payment (Mobile money and credit card) for Taxi-brousses and first class transport in Madagascar

**Wellcoloc**

- FR : Site de recherche locataire, co-locataire, propriétaire locataire
- EN : Tenant search site, roommate, landlord tenant

**E-rdv**

- FR : Application de réservation de rendez-vous auprès des professionnels
- EN : Application for booking appointments with professionals

**DFC Com**

- FR : Spécialiste de la communication par l'objet et de la fabrication sur mesure
- EN : Specialist in object communication and custom manufacturing

**Fashionshopgroup**

- Résumé FR : Fashionshopgroup sélectionne les plus beaux articles de mode féminine autour du monde
- Détail FR : … pour vous donner toute satisfaction
- EN : Fashionshopgroup selects the most beautiful women's fashion items around the world (to give you complete satisfaction)

**Alter Smoke**

- Résumé FR : Alter Smoke, 50 magasins et boutiques de cigarette électronique et e-Liquide en France
- Détail FR : … en France (15 à Paris), en Belgique et Suisse
- EN : Alter Smoke, 50 electronic cigarette and e-Liquid stores and shops in France (15 in Paris), Belgium and Switzerland

**Chronolivraison**

- FR : Service de livraison rapide, connecté à Antananarivo et provinces
- EN : Fast delivery service, connected to Antananarivo and provinces

**Euromputer / Eurocomputer**

- Résumé FR : Recode répare vos imprimantes, scanners code-barres en 24/72h
- Détail FR : … achat et location de matériel de traçabilité neuf ou reconditionné depuis 1994
- EN : Recode repairs your printers, barcode scanners in 24/72h, purchase and rental of new or reconditioned traceability equipment since 1994
- Note : le domaine est `eurocomputer.fr` ; titre « Euromputer » et client « Eurcomputer » sont des coquilles. La description parle de « Recode » (marque ?) — à clarifier.

**Emarket.mg**

- Résumé FR : 1er site multivendeurs à Madagascar, livraison vers l'Europe prochainement
- Détail FR : 1er site multivendeurs à Madagascar, paiement avec mobile money et carte visa/mastercard, livraison vers l'Europe prochainement
- EN : 1st multi-seller site in Madagascar, payment with mobile money and visa/mastercard, delivery to Europe soon
- Note : « prochainement » / « soon » est daté (2021) — à reformuler ou valider.

**Dialopro**

- Résumé FR : DIALOPRO est une entreprise spécialisée en relation client et en BPO
- Détail FR : Chez DIALOPRO, nous créons une véritable connexion avec nos clients. Votre réussite est aussi la nôtre. _(texte du client, pas de Genius)_
- EN : DIALOPRO is a company specializing in customer relations and BPO · At DIALOPRO, we create a real connection with our customers. Your success is also ours.
- Note : le bureau Madagascar de Genius est hébergé à l'« Établissement Dialopro ».

**Miarakap**

- Résumé FR : Fonds d'investissement à impact dédié aux PME et startups à Madagascar
- Détail FR : Le premier fonds d'investissement à impact à Madagascar
- EN : Impact investment fund dedicated to SMEs and startups in Madagascar · The first impact investment fund in Madagascar

**Evasommeil** _(EN uniquement)_

- Résumé : Medical Data Analysis Platform, Polygraphy and Polysomnography
- Détail : Brain. Ventilation . Analysis – Analysis Platform, Polygraphy & Polysomnography
- → Traduction FR à produire sans ajout d'information.

### 5.3 Catégories et technologies par projet

Référence = **taxonomies FR** (les pages catégories EN sont incomplètes). Les écarts EN sont signalés.

| Projet                     | Catégories (FR)                                             | Technologies (FR)                                 | Écarts EN                                                                                 |
| -------------------------- | ----------------------------------------------------------- | ------------------------------------------------- | ----------------------------------------------------------------------------------------- |
| MagicStay                  | Design UX/UI, Innovation, Voyage                            | Bootstrap, jQuery                                 | EN ajoute catégories App Web, Site vitrine ; technos Cordova, Framework7, WordPress, Zend |
| Transparency International | App Web, Design UX/UI, Site vitrine                         | jQuery, Zend                                      | —                                                                                         |
| SQVF / DGI                 | App Web, Innovation                                         | jQuery, Node.js, Zend                             | EN ajoute Bootstrap                                                                       |
| Taxibrousse.mg             | App Web, Design UX/UI, E-commerce, Innovation, Voyage       | Bootstrap, Cordova, Framework7, jQuery, Zend      | —                                                                                         |
| Wellcoloc                  | Design UX/UI, E-commerce, Innovation, Voyage                | Bootstrap, jQuery, Laravel, Node.js, Nuxt, Vue.js | —                                                                                         |
| E-rdv                      | App Web, Design UX/UI, Innovation                           | Bootstrap, jQuery, Node.js, Zend                  | EN ajoute E-commerce                                                                      |
| DFC Com                    | Design UX/UI                                                | jQuery, Node.js, Zend                             | EN ajoute E-commerce, Site vitrine                                                        |
| Fashionshopgroup           | E-commerce, Innovation                                      | Bootstrap, jQuery, PrestaShop                     | EN ajoute Design UX/UI                                                                    |
| Alter Smoke                | E-commerce                                                  | PrestaShop                                        | —                                                                                         |
| Chronolivraison            | App Web, Design UX/UI, E-commerce, Innovation, Site vitrine | Bootstrap, Cordova, Framework7, jQuery, Laravel   | —                                                                                         |
| Euromputer                 | App Web, Design UX/UI, E-commerce, Innovation               | Bootstrap, jQuery, Zend                           | EN ajoute Site vitrine                                                                    |
| Emarket.mg                 | Design UX/UI, E-commerce, Innovation                        | Bootstrap, jQuery, Laravel                        | EN ajoute Zend                                                                            |
| Dialopro                   | Design UX/UI, Innovation, Site vitrine                      | Bootstrap, jQuery, Zend                           | —                                                                                         |
| Miarakap                   | Site vitrine                                                | Bootstrap, jQuery, Zend                           | EN ajoute Design UX/UI, Innovation                                                        |
| Evasommeil                 | Design UX/UI, Innovation, Site vitrine, App Web _(EN)_      | jQuery, Node.js                                   | —                                                                                         |

**Catégories existantes** (FR → EN) : App Web → Web app · Design UX/UI → Design UX/UI · E-commerce → E-commerce · Innovation → Innovation · Site vitrine → Showcase site · Voyage → Travel

> Remarques pour la refonte :
>
> - « Innovation » est appliquée à 11 projets sur 15 → n'a plus de valeur de filtre. Proposition : la conserver comme _tag_ mais pas comme filtre principal.
> - **Aucune catégorie « Mobile »** n'existe, alors que Taxibrousse.mg et Chronolivraison ont une app mobile (Cordova/Framework7). Ajouter « Mobile » à ces deux projets est justifié par les données réelles.
> - « Business software » n'existe pas comme catégorie ; seul SQVF/DGI y correspond objectivement.

---

## 6. Technologies

### 6.1 Technologies affichées (section « Nos stacks techniques »)

Laravel · Symfony · ReactJs · VueJs · WordPress · PrestaShop · NodeJs · Spring — logos PNG 500×237.

### 6.2 Technologies réellement utilisées dans les projets (taxonomie « stacks »)

| Techno         | Nb projets                 | Affichée sur la homepage ? |
| -------------- | -------------------------- | -------------------------- |
| jQuery         | 13                         | Non                        |
| Bootstrap      | 11                         | Non                        |
| Zend Framework | 10                         | **Non** ⚠️                 |
| Node.js        | 5                          | Oui                        |
| Laravel        | 3                          | Oui                        |
| Cordova        | 2                          | Non                        |
| Framework7     | 2                          | Non                        |
| PrestaShop     | 2                          | Oui                        |
| Nuxt           | 1                          | Non                        |
| Vue.js         | 1                          | Oui                        |
| WordPress      | 0 (FR) / 1 (EN, MagicStay) | Oui                        |
| Symfony        | 0                          | Oui                        |
| React          | 0                          | Oui                        |
| Spring         | 0                          | Oui                        |

> Zend est la techno back-end la plus utilisée dans le portfolio mais n'est pas affichée. Symfony, React et Spring sont affichés sans projet associé.

### 6.3 Stack actuelle à présenter (fournie par le brief, `specs/prompt.md` §12)

- **Frontend** : Astro, TypeScript, React, Tailwind CSS, GSAP, Sanity, SEO / Schema.org, Cloudflare
- **Backend** : Symfony, PHP 8.x, API Platform, MySQL, Redis, JWT, OAuth, Symfony Messenger, Symfony Mailer
- **Infrastructure** : Cloudflare, Docker, GitHub, GitHub Actions, CI/CD

**Proposition de présentation** : distinguer « Stack actuelle » (brief) et « Technologies maîtrisées / historique projets » (Laravel, Vue/Nuxt, Node.js, Zend, PrestaShop, WordPress, Cordova, Framework7, Spring, React). Spring est conservé car affiché par l'entreprise.

---

## 7. Contacts, bureaux, réseaux

### 7.1 Coordonnées

| Champ                | Valeur actuelle                                                                      | Remarque                                                                     |
| -------------------- | ------------------------------------------------------------------------------------ | ---------------------------------------------------------------------------- |
| Email                | `contact@genius-at-work.com`                                                         | Affiché obfusqué `contact[@]genius-at-work.com` et le `mailto:` est cassé ⚠️ |
| Téléphone 1 (MG)     | +261 34 85 311 20                                                                    |                                                                              |
| Téléphone 2 (MG)     | +261 34 45 819 21                                                                    |                                                                              |
| Bureau France        | 12 Allée Metafram, 38640 Claix (affiché « Grenoble, 12 Allée Metafram 38640 claix ») | Claix est une commune de l'agglomération grenobloise                         |
| Téléphone France     | 07 50 21 81 06 → +33 7 50 21 81 06                                                   | Affiché non formaté, pas de lien `tel:`                                      |
| Bureau Madagascar    | Établissement Dialopro, Rue Tsiadana, Antananarivo, Madagascar                       |                                                                              |
| Téléphone Madagascar | +261 34 85 311 20                                                                    | = Téléphone 1                                                                |

### 7.2 Réseaux sociaux

| Réseau   | URL                                             | Remarque                                                                        |
| -------- | ----------------------------------------------- | ------------------------------------------------------------------------------- |
| Facebook | https://www.facebook.com/geniusatworksarl       | Révèle la forme juridique : **SARL**                                            |
| LinkedIn | https://www.linkedin.com/in/genius-at-work-com/ | URL de type **profil personnel** (`/in/`), pas page entreprise (`/company/`) ⚠️ |

### 7.3 Formulaire de contact actuel

Champs (tous requis) : Nom et prénom · Téléphone · Email · Sujet · Message (max 2000 car.) — bouton « Envoyer votre demande ». Anti-spam : hCaptcha.

### 7.4 Équipe

3 fiches nominatives, sans rôle ni photo ni bio : **Hariony RAKOTOBE**, **Jean François**, **Voniarisoa FIN**. Non affichées sur la homepage.

---

## 8. Assets

Tous téléchargés dans `assets/legacy/`.

| Asset               | Fichier                            | Format                                                     | Réutilisable ?                                                                                                |
| ------------------- | ---------------------------------- | ---------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------- |
| Logo clair          | `logo/logo.png`                    | PNG 300×110, monogramme « GAW SERVICES » bleu cyan + lilas | Oui, en attendant un **SVG** (à demander)                                                                     |
| Logo sombre         | `logo/logo-dark.png`               | PNG 300×110                                                | Idem                                                                                                          |
| Favicon             | `logo/cropped-favicon-270x270.png` | PNG 270×270                                                | Oui (à décliner en SVG/ICO)                                                                                   |
| Visuels projets ×15 | `projects/*.jpg` / `tiim-1.png`    | 1270×950, 220–800 Ko                                       | Oui : captures d'écran desktop des sites → à convertir en AVIF/WebP et à mettre en scène (mockups navigateur) |
| Logos technos ×8    | `technologies/*.png`               | PNG 500×237                                                | Non recommandé : remplacer par SVG officiels monochromes                                                      |
| Image hero          | `misc/slide.png`                   | PNG 590×394                                                | **Non** : basse définition, vraisemblablement une photo de banque d'images                                    |

**Manquants** : logo vectoriel, photos d'équipe/bureaux, logos clients, captures mobiles (Taxibrousse, Chronolivraison), captures intérieures des applications (SQVF, E-rdv…).

---

## 9. Informations légales

| Élément                       | État                                                                          |
| ----------------------------- | ----------------------------------------------------------------------------- |
| Raison sociale                | « Genius at work Services » (title/footer) ; « SARL » d'après l'URL Facebook  |
| SIREN / SIRET / RCS / capital | **Absents**                                                                   |
| Directeur de publication      | **Absent**                                                                    |
| Hébergeur                     | **Absent**                                                                    |
| Mentions légales              | **Absentes** (obligatoires en France — LCEN art. 6)                           |
| Politique de confidentialité  | **Absente** — la page « Termes et conditions » est un lorem ipsum du template |
| Cookies                       | Aucun bandeau ; hCaptcha (tiers) chargé                                       |

---

## 10. Éléments à conserver

- Nom et signature **Genius@work** / Genius At Work.
- Positionnement : agence web, innovation digitale, solutions sur mesure.
- Les chiffres réels : **15 ans d'expérience**, **plus de 20 personnes**.
- Valeurs : **performance** et **confiance**.
- Les 6 services, leur ordre et leur sens.
- Le processus en 4 étapes **Empathie → Expertise → Solution → Développement**, méthode Scrum.
- Les 15 projets, leurs liens, dates, clients, catégories, technologies.
- Le récit MagicStay.
- Double implantation **France (Claix / Grenoble) + Madagascar (Antananarivo)**.
- Coordonnées, Facebook, LinkedIn.
- Les captures projets.
- Bilinguisme FR/EN avec hreflang.

## 11. Éléments à améliorer

| Problème                                                                             | Action proposée                                                                                            |
| ------------------------------------------------------------------------------------ | ---------------------------------------------------------------------------------------------------------- |
| Page SQVF en erreur 500                                                              | Reconstruire à partir des données EN disponibles                                                           |
| Pages services/technos/équipe vides                                                  | Les supprimer comme pages autonomes ou les enrichir (avec validation) ; services → page `/services/` riche |
| Termes & conditions = lorem ipsum                                                    | Rédiger `/legal/` + `/privacy-policy/` avec placeholders identifiés pour les données légales manquantes    |
| Libellés FR sur les pages EN                                                         | i18n complète                                                                                              |
| Title identique FR/EN, meta keywords obsolète                                        | SEO par page et par langue                                                                                 |
| Placeholder « Notre adresse de contact ici. »                                        | Remplacer                                                                                                  |
| Ton générique (« projets pharaoniques », « génies talentueux », « agence web 360° ») | Réécriture éditoriale, même sens                                                                           |
| Incohérences FR/EN (catégories, technos, dates)                                      | Fusion documentée (§5.3), validation client                                                                |
| Coquilles : Euromputer / Eurcomputer, Magistay, Transparency Internationale          | Corriger                                                                                                   |
| Email obfusqué cassé                                                                 | Lien `mailto:` valide + protection anti-spam côté formulaire                                               |
| Logos technos non alignés avec les projets                                           | Visualisation par couche (§6.3)                                                                            |
| « Innovation » sur 11/15 projets                                                     | Tag, pas filtre                                                                                            |
| Pas de catégorie Mobile                                                              | L'ajouter pour Taxibrousse.mg et Chronolivraison                                                           |
| ~25 scripts jQuery/vendor, hCaptcha global                                           | Astro, JS minimal, anti-spam honeypot + rate limit                                                         |
| `xmlrpc.php`, `wp-json`, feeds exposés                                               | Disparaissent avec la migration ; prévoir redirections 301                                                 |

## 12. Éléments manquants (à demander au client — ne seront pas inventés)

1. **Mentions légales** : raison sociale exacte, forme, capital, SIREN/RCS, adresse du siège, directeur de publication, hébergeur.
2. **Rôles et photos** de Hariony RAKOTOBE, Jean François, Voniarisoa FIN — et accord pour les afficher.
3. **Logo vectoriel** (SVG) et charte existante éventuelle.
4. Pour chaque projet : challenge, solution, contribution exacte de Genius, résultats chiffrés, captures supplémentaires — sinon les pages projet afficheront uniquement les données disponibles.
5. Signification de **SQVF** et autorisation de publier le projet DGI.
6. Validation des **écarts FR/EN** (§5.3), notamment MagicStay (technos, dates, URL).
7. Statut des produits « client : Genius » (Taxibrousse.mg, Chronolivraison, Emarket.mg, E-rdv) — toujours en ligne / opérés ?
8. Accord pour publier les **noms de clients personnes physiques**.
9. URL de la **page LinkedIn entreprise** si elle existe.
10. Témoignages clients réels (aucun actuellement).
11. Services suggérés par les meta keywords (TMA, infogérance, conseil, référencement, .NET, Java) : à confirmer ou écarter.
12. Adresse email de réception du formulaire et éventuels destinataires par bureau.

---

## 13. Plan de redirections 301 (préparation migration)

| Ancienne URL                                                                       | Nouvelle URL                            |
| ---------------------------------------------------------------------------------- | --------------------------------------- |
| `/portfolios/`                                                                     | `/projects/`                            |
| `/en/portfolios/`                                                                  | `/en/projects/`                         |
| `/portfolios/{slug}/`                                                              | `/projects/{slug}/`                     |
| `/portfolios/transparency-internationale/`                                         | `/projects/transparency-international/` |
| `/en/portfolios/{slug}/`                                                           | `/en/projects/{slug}/`                  |
| `/portfolios-categories/{cat}/`                                                    | `/projects/?category={cat}`             |
| `/portfolios-stacks/*`, `/en/portfolios-stacks/*`                                  | `/technology/` · `/en/technology/`      |
| `/services/{slug}/`                                                                | `/services/#{slug}`                     |
| `/en/services/{slug}/`                                                             | `/en/services/#{slug}`                  |
| `/technos/*`, `/en/technos/*`                                                      | `/technology/` · `/en/technology/`      |
| `/team/*`, `/en/team/*`                                                            | `/about/` · `/en/about/`                |
| `/termes-et-conditions/`                                                           | `/legal/`                               |
| `/en/terms-and-conditions/`                                                        | `/en/legal/`                            |
| `/bonjour-tout-le-monde/`, `/author/*`, `/category/*`, `/home-fr/`, `/en/home-en/` | `/` ou 410                              |
