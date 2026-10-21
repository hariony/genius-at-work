<?php

declare(strict_types=1);

namespace App\Application\Content\Page;

/**
 * Toutes les pages et tous les textes partagés du site, avec la forme de leurs champs.
 * Même structure côté site : apps/web/src/types/pages.ts. Un test vérifie que le contenu
 * initial (data/content-seed.json) correspond exactement à ces descriptions.
 */
final class PageCatalog
{
    public const string PAGES = 'Pages';
    public const string SHARED = 'Blocs communs';
    public const string LEGAL = 'Juridique';

    /** @var array<string, PageDefinition>|null */
    private static ?array $pages = null;

    /** @return array<string, PageDefinition> par identifiant, dans l'ordre d'affichage */
    public static function all(): array
    {
        return self::$pages ??= self::build();
    }

    public static function get(string $id): ?PageDefinition
    {
        return self::all()[$id] ?? null;
    }

    /** @return array<string, PageDefinition> */
    private static function build(): array
    {
        $pages = [
            new PageDefinition('home', 'Accueil', self::PAGES, 'En-tête et titres des sections de la page d’accueil, chiffres clés.', '/', [
                Field::group('hero', 'En-tête', [
                    ...self::seo(),
                    Field::line('eyebrow', 'Surtitre'),
                    Field::line('title', 'Titre'),
                    Field::line('titleHighlight', 'Fin du titre')->hint('Affichée en couleur, à la suite du titre.'),
                    Field::lines('verbs', 'Verbes du titre', 4)->hint('Un par ligne. Le premier doit figurer dans le titre : il y est remplacé à tour de rôle par les suivants.'),
                    Field::text('lead', 'Chapeau'),
                    Field::line('scroll', 'Indication de défilement'),
                    Field::group('visual', 'Animation « sous charge »', [
                        Field::line('caption', 'Légende du compteur')->hint('Le compteur montre les requêtes de l’animation : jamais un chiffre réel.'),
                        Field::line('hint', 'Invitation à interagir'),
                        Field::list('layers', 'Couches', [
                            Field::line('discipline', 'Discipline'),
                            Field::line('layer', 'Couche'),
                        ], 4, 4)->hint('De l’entrée vers les données. La discipline s’affiche au-dessus de la couche.'),
                        Field::line('clock', 'Horloge')->hint('{time} et {city} sont remplacés par l’heure et la ville du premier bureau.'),
                        Field::group('moments', 'Moments de la journée', [
                            Field::line('dawn', 'Aube (5 h – 8 h)'),
                            Field::line('day', 'Jour (8 h – 17 h)'),
                            Field::line('dusk', 'Soir (17 h – 20 h)'),
                            Field::line('night', 'Nuit (20 h – 5 h)'),
                        ]),
                    ]),
                    Field::group('strip', 'Bandeau du bas', [
                        Field::line('services', 'Métiers')->hint('{n} est remplacé par le nombre de services publiés.'),
                        Field::line('featured', 'Projet à la une')->hint('Suivi du nom du premier projet mis en avant.'),
                    ]),
                ]),
                Field::group('sections', 'Sections', [
                    Field::group('hero', 'Visuel de l’en-tête', [
                        Field::line('badge', 'Projets publiés')->hint('{n} est remplacé par le nombre de projets publiés.'),
                    ]),
                    Field::group('work', 'Section Réalisations', [
                        ...self::chapter(),
                        Field::line('all', 'Lien vers toutes les réalisations'),
                        Field::line('visit', 'Lien vers un projet'),
                    ]),
                    Field::group('manifesto', 'Section Manifeste', [
                        Field::line('label', 'Étiquette'),
                        Field::text('text', 'Texte', 5),
                        Field::list('stats', 'Chiffres clés', [
                            Field::number('value', 'Nombre'),
                            Field::line('suffix', 'Suffixe')->shared()->optional()->hint('Par exemple « + ».'),
                            Field::line('label', 'Légende'),
                        ], 1, 4)->hint('Uniquement des chiffres vérifiables.'),
                    ]),
                    Field::group('services', 'Section Services', self::chapter()),
                    Field::group('method', 'Section Méthode', self::chapter()),
                    Field::group('reasons', 'Section Pourquoi nous', [
                        Field::line('label', 'Étiquette'),
                        Field::line('title', 'Titre'),
                    ]),
                ]),
            ]),
            new PageDefinition('about', 'À propos', self::PAGES, 'Présentation de l’équipe, valeurs, bureau.', '/about/', [
                self::hero(),
                Field::group('story', 'L’équipe', [
                    Field::line('label', 'Étiquette'),
                    Field::line('title', 'Titre'),
                    Field::lines('paragraphs', 'Paragraphes', 8)->hint('Un paragraphe par ligne.'),
                ]),
                Field::group('values', 'Valeurs', [
                    Field::line('label', 'Étiquette'),
                    Field::line('title', 'Titre'),
                    self::items('Valeurs'),
                ]),
                Field::group('offices', 'Bureau', self::chapter()),
            ]),
            new PageDefinition('services-page', 'Services', self::PAGES, 'Page qui liste les services, et textes communs aux pages de service.', '/services/', [
                self::hero(),
                Field::group('list', 'Liste des services', self::chapter()),
                Field::group('detail', 'Pages de service', [
                    Field::line('scopeLabel', 'Étiquette « Périmètre »'),
                    Field::line('scopeTitle', 'Titre « Périmètre »'),
                    Field::line('techLabel', 'Étiquette « Technologies »'),
                    Field::line('techTitle', 'Titre « Technologies »'),
                    Field::line('projectsLabel', 'Étiquette « Réalisations »'),
                    Field::line('projectsTitle', 'Titre « Réalisations »'),
                    Field::line('methodLabel', 'Étiquette « Méthode »'),
                    Field::line('otherServices', 'Titre « Autres services »'),
                    Field::line('seoSuffix', 'Fin de la description pour Google')->hint('Ajoutée après le nom du service.'),
                ]),
            ]),
            new PageDefinition('ai-page', 'IA & automatisation', self::PAGES, 'Page dédiée à l’offre IA : capacités, cas d’usage, chaîne complète, technologies.', '/services/ai/', [
                Field::group('hero', 'En-tête', [
                    ...self::seo(),
                    Field::line('eyebrow', 'Surtitre'),
                    Field::line('title', 'Titre'),
                    Field::text('lead', 'Chapeau'),
                    Field::line('secondaryCta', 'Lien vers les cas d’usage'),
                ]),
                Field::group('capabilities', 'Capacités', [
                    ...self::chapter(),
                    Field::list('items', 'Domaines', [
                        Field::line('title', 'Titre'),
                        Field::text('description', 'Description'),
                        Field::lines('examples', 'Exemples', 6)->hint('Un par ligne.'),
                    ], 1, 6),
                ]),
                Field::group('useCases', 'Cas d’usage', [...self::chapter(), self::items('Cas d’usage')]),
                Field::group('chain', 'Chaîne complète', [...self::chapter(), self::items('Étapes', 'steps')]),
                Field::group('approach', 'Notre approche', [
                    Field::line('label', 'Étiquette'),
                    Field::line('title', 'Titre'),
                    Field::lines('not', 'Ce que nous ne faisons pas', 5)->hint('Un point par ligne.'),
                    Field::text('answer', 'Ce que nous faisons'),
                ]),
                Field::group('technologies', 'Technologies', [
                    ...self::chapter(),
                    Field::list('groups', 'Groupes', [
                        Field::line('title', 'Nom du groupe'),
                        Field::lines('technologies', 'Technologies', 8)->shared()->hint('Une par ligne.'),
                    ], 1, 6),
                ]),
                Field::group('method', 'Méthode', self::chapter())->hint('Les étapes viennent du bloc commun « Méthode et atouts ».'),
                Field::group('caseStudies', 'Réalisations', self::chapter())->hint('Section affichée seulement si un projet publié porte la catégorie « IA ».'),
                Field::group('cta', 'Appel final', [
                    Field::line('label', 'Étiquette'),
                    Field::line('title', 'Titre'),
                    Field::text('text', 'Texte'),
                    Field::line('button', 'Bouton'),
                ]),
            ]),
            new PageDefinition('projects-page', 'Réalisations', self::PAGES, 'Page portfolio et textes communs aux pages projet.', '/projects/', [
                self::hero(),
                Field::section('Filtres', [
                    Field::line('filterLabel', 'Libellé des filtres'),
                    Field::line('all', 'Filtre « Tous »'),
                    Field::line('countOne', 'Compteur (1 projet)')->hint('{n} est remplacé par le nombre.'),
                    Field::line('countMany', 'Compteur (plusieurs projets)'),
                ]),
                Field::group('categoryLabels', 'Catégories de projet', [
                    Field::line('web-app', 'App web'),
                    Field::line('mobile', 'Mobile'),
                    Field::line('ux-ui', 'Design UX/UI'),
                    Field::line('e-commerce', 'E-commerce'),
                    Field::line('innovation', 'Innovation'),
                    Field::line('showcase', 'Site vitrine'),
                    Field::line('travel', 'Voyage'),
                    Field::line('ai', 'IA'),
                ])->hint('Noms affichés dans les filtres, les fiches et les cartes de projet, et dans ce back-office.'),
                Field::group('detail', 'Pages projet', [
                    Field::line('client', 'Libellé « Client »'),
                    Field::group('clientTypes', 'Types de client', [
                        Field::line('company', 'Entreprise'),
                        Field::line('institution', 'Institution'),
                        Field::line('ngo', 'ONG'),
                        Field::line('in-house', 'Produit interne'),
                    ]),
                    Field::line('period', 'Libellé « Période »'),
                    Field::line('categories', 'Libellé « Catégories »'),
                    Field::line('technologies', 'Libellé « Technologies »'),
                    Field::line('services', 'Libellé « Services »'),
                    Field::line('visit', 'Lien « Voir le site »'),
                    Field::line('overview', 'Titre « Le projet »'),
                    Field::line('previous', 'Lien « Projet précédent »'),
                    Field::line('next', 'Lien « Projet suivant »'),
                    Field::line('back', 'Lien « Toutes les réalisations »'),
                    Field::line('more', 'Titre « Autres réalisations »'),
                ]),
            ]),
            new PageDefinition('technology-page', 'Technologie', self::PAGES, 'Approche technique, technologies en production, principes.', '/technology/', [
                self::hero(),
                Field::group('usage', 'Technologies en production', [
                    ...self::chapter(),
                    Field::line('projectsOne', 'Compteur (1 projet)')->hint('{n} est remplacé par le nombre.'),
                    Field::line('projectsMany', 'Compteur (plusieurs projets)'),
                ]),
                Field::group('engineering', 'Stack technique', self::chapter())->hint('Les couches et leurs technologies viennent du bloc commun « Stack technique ».'),
                Field::group('principles', 'Principes', [
                    Field::line('label', 'Étiquette'),
                    Field::line('title', 'Titre'),
                    self::items('Principes'),
                ]),
            ]),
            new PageDefinition('contact', 'Contact', self::PAGES, 'En-tête et encadrés de la page Contact (le formulaire a ses propres textes).', '/contact/', [
                Field::section('En-tête', [
                    ...self::seo(),
                    Field::line('eyebrow', 'Surtitre'),
                    Field::line('title', 'Titre'),
                    Field::text('lead', 'Chapeau'),
                ]),
                Field::group('direct', 'Contact direct', [
                    Field::line('label', 'Titre'),
                    Field::line('email', 'Libellé « E-mail »'),
                    Field::line('phones', 'Libellé « Téléphone »'),
                ]),
                Field::group('offices', 'Bureau', self::chapter()),
            ]),
            new PageDefinition('not-found', 'Page introuvable (404)', self::PAGES, 'Affichée pour une adresse qui n’existe pas.', null, [
                Field::section('En-tête', [
                    ...self::seo(),
                    Field::line('eyebrow', 'Surtitre'),
                    Field::line('title', 'Titre'),
                    Field::text('lead', 'Chapeau'),
                ]),
                Field::section('Liens proposés', [
                    Field::list('links', 'Liens', [
                        Field::line('label', 'Texte du lien'),
                        Field::line('href', 'Adresse')->shared()->hint('Chemin du site, par exemple /projects/ (la version anglaise est déduite).'),
                    ], 1, 6),
                ]),
            ]),

            new PageDefinition('ai-section', 'Bloc IA (accueil)', self::SHARED, 'Section IA de la page d’accueil : trois voies (modèles, connaissance, agents) et lien vers la page IA.', '/', [
                Field::section('Textes', [
                    ...self::chapter(),
                    Field::line('input', 'Entrée du schéma'),
                    Field::line('output', 'Sortie du schéma'),
                    Field::line('cta', 'Lien vers la page IA'),
                ]),
                Field::section('Voies', [
                    Field::list('lanes', 'Voies', [
                        Field::line('title', 'Nom'),
                        Field::line('caption', 'Rôle'),
                        Field::lines('examples', 'Exemples', 4)->hint('Un par ligne.'),
                    ])->fixed(3),
                ]),
            ]),
            new PageDefinition('method', 'Méthode et atouts', self::SHARED, 'Étapes de la méthode et raisons de nous choisir (accueil, pages de service).', '/', [
                Field::section('Méthode', [self::items('Étapes', 'steps')]),
                Field::section('Pourquoi nous', [self::items('Atouts', 'reasons')->hint('Uniquement des faits vérifiables.')]),
            ]),
            new PageDefinition('technologies', 'Stack technique', self::SHARED, 'Couches de la stack (page Technologie).', '/technology/', [
                Field::section('Stack, couche par couche', [
                    Field::list('stack', 'Couches', [
                        Field::choice('layer', 'Couche', [
                            'frontend' => 'Interface',
                            'cms' => 'Contenu',
                            'api' => 'API',
                            'backend' => 'Métier',
                            'data' => 'Données',
                            'infrastructure' => 'Infrastructure',
                            'cicd' => 'Livraison',
                        ])->hint('Détermine l’icône.'),
                        Field::line('title', 'Nom'),
                        Field::line('role', 'Rôle'),
                        Field::lines('technologies', 'Technologies')->shared()->hint('Une par ligne.'),
                    ]),
                ]),
            ]),
            new PageDefinition('contact-form', 'Formulaire de contact', self::SHARED, 'Étapes, libellés, choix proposés, messages d’erreur et de confirmation.', '/contact/', [
                Field::section('Étapes', [
                    Field::list('steps', 'Étapes', [
                        Field::line('title', 'Titre'),
                        Field::line('description', 'Description'),
                    ])->fixed(3),
                    Field::line('stepCounter', 'Compteur d’étapes')->hint('{current} et {total} sont remplacés par les numéros.'),
                ]),
                Field::group('projectType', 'Type de projet', [
                    Field::line('legend', 'Question'),
                    Field::group('options', 'Choix', [
                        Field::line('website', 'Site internet'),
                        Field::line('mobile-app', 'Application mobile'),
                        Field::line('web-app', 'Application web'),
                        Field::line('business-software', 'Logiciel métier'),
                        Field::line('e-commerce', 'E-commerce'),
                        Field::line('ai', 'IA & automatisation'),
                        Field::line('maintenance', 'Maintenance'),
                        Field::line('other', 'Autre'),
                    ]),
                ]),
                Field::group('message', 'Message', [
                    Field::line('label', 'Libellé'),
                    Field::line('hint', 'Aide'),
                    Field::line('counter', 'Compteur')->hint('{count} et {max} sont remplacés par les nombres.'),
                ]),
                Field::group('timeline', 'Échéance', [
                    Field::line('legend', 'Question'),
                    Field::group('options', 'Choix', [
                        Field::line('asap', 'Dès que possible'),
                        Field::line('1-3-months', '1 à 3 mois'),
                        Field::line('3-6-months', '3 à 6 mois'),
                        Field::line('flexible', 'Pas d’urgence'),
                    ]),
                ]),
                Field::section('Coordonnées', [
                    Field::line('optional', 'Mention « facultatif »'),
                    Field::line('fullName', 'Nom et prénom'),
                    Field::line('email', 'E-mail'),
                    Field::line('phone', 'Téléphone'),
                    Field::line('company', 'Entreprise'),
                    Field::line('honeypot', 'Champ piège (lu par les lecteurs d’écran)'),
                ]),
                Field::group('consent', 'Accord', [
                    Field::text('before', 'Texte avant le lien', 2),
                    Field::line('link', 'Lien vers la politique de confidentialité'),
                    Field::line('after', 'Texte après le lien'),
                ]),
                Field::group('turnstile', 'Vérification anti-robot', [
                    Field::line('label', 'Libellé'),
                    Field::line('pending', 'En cours'),
                    Field::line('failed', 'Échec'),
                ]),
                Field::group('actions', 'Boutons', [
                    Field::line('next', 'Continuer'),
                    Field::line('previous', 'Retour'),
                    Field::line('submit', 'Envoyer'),
                    Field::line('submitting', 'Envoi en cours'),
                    Field::line('restart', 'Nouvelle demande'),
                ]),
                Field::group('errors', 'Messages d’erreur', [
                    Field::line('summary', 'Résumé'),
                    Field::line('required', 'Champ obligatoire'),
                    Field::line('too_short', 'Texte trop court'),
                    Field::line('too_long', 'Texte trop long'),
                    Field::line('invalid_email', 'E-mail invalide'),
                    Field::line('invalid_phone', 'Téléphone invalide'),
                    Field::line('invalid_choice', 'Choix manquant'),
                    Field::line('consent_required', 'Accord manquant'),
                    Field::text('rateLimited', 'Trop d’envois', 2),
                    Field::text('network', 'Problème de connexion', 2),
                    Field::text('server', 'Erreur du serveur', 2),
                ]),
                Field::group('success', 'Confirmation', [
                    Field::line('title', 'Titre'),
                    Field::line('reference', 'Libellé « Référence »'),
                    Field::text('body', 'Texte'),
                    Field::line('next', 'Titre « En attendant »'),
                    Field::line('projects', 'Lien vers les réalisations'),
                ]),
            ]),
            new PageDefinition('interface', 'Menus et pied de page', self::SHARED, 'Navigation, boutons d’appel, pied de page, libellés d’accessibilité.', '/', [
                Field::group('nav', 'Navigation', [
                    Field::line('home', 'Accueil'),
                    Field::line('services', 'Services'),
                    Field::line('ai', 'IA'),
                    Field::line('projects', 'Réalisations'),
                    Field::line('technology', 'Technologie'),
                    Field::line('about', 'À propos'),
                    Field::line('contact', 'Contact'),
                    Field::line('menu', 'Bouton « Menu »'),
                    Field::line('close', 'Bouton « Fermer »'),
                ]),
                Field::group('cta', 'Boutons d’appel', [
                    Field::line('discussProject', 'Parler de votre projet'),
                    Field::line('seeProjects', 'Voir les réalisations'),
                ]),
                Field::group('footer', 'Pied de page', [
                    Field::line('ctaLabel', 'Étiquette de l’appel'),
                    Field::line('cta', 'Titre de l’appel'),
                    Field::text('ctaText', 'Texte de l’appel'),
                    Field::text('tagline', 'Présentation de l’agence'),
                    Field::line('navigation', 'Titre « Navigation »'),
                    Field::line('offices', 'Titre « Bureau »'),
                    Field::line('follow', 'Titre « Suivre »'),
                    Field::line('localTime', 'Libellé « Heure locale »'),
                    Field::line('legal', 'Lien « Mentions légales »'),
                    Field::line('privacy', 'Lien « Confidentialité »'),
                    Field::line('rights', 'Mention « Tous droits réservés »'),
                    Field::line('backToTop', 'Lien « Haut de page »'),
                ]),
                Field::group('office', 'Bureaux', [
                    Field::line('coordinates', 'Libellé « Coordonnées »'),
                ]),
                Field::group('a11y', 'Accessibilité (lecteurs d’écran)', [
                    Field::line('skipToContent', 'Aller au contenu'),
                    Field::line('mainNav', 'Navigation principale'),
                    Field::line('languageSwitcher', 'Changer de langue'),
                    Field::line('breadcrumb', 'Fil d’Ariane'),
                    Field::line('newTab', 'Nouvel onglet'),
                    Field::line('backToTop', 'Bouton « Retour en haut »')->hint('Nom du bouton flottant, lu par les lecteurs d’écran et affiché au survol.'),
                ]),
            ]),

            new PageDefinition('legal', 'Mentions légales', self::LEGAL, 'Éditeur, hébergeur, propriété intellectuelle. Tant qu’une information obligatoire manque, la page n’est pas indexée par Google.', '/legal/', [
                Field::section('En-tête', [...self::seo(), Field::line('title', 'Titre'), Field::line('missing', 'Mention « À compléter »')]),
                Field::group('publisherInfo', 'Éditeur du site', [
                    Field::line('name', 'Raison sociale')->shared(),
                    ...array_map(static fn (Field $f): Field => $f->shared()->optional(), [
                        Field::line('legalForm', 'Forme juridique'),
                        Field::line('capital', 'Capital social'),
                        Field::line('rcs', 'RCS'),
                        Field::line('siret', 'SIRET'),
                        Field::line('vat', 'TVA intracommunautaire'),
                        Field::line('director', 'Directeur de la publication'),
                        Field::line('address', 'Siège'),
                        Field::line('phone', 'Téléphone'),
                    ]),
                ])->hint('Laissez vide ce qui n’est pas encore connu : « À compléter » s’affiche à la place. L’e-mail est celui des Réglages.'),
                Field::group('hostInfo', 'Hébergeur', array_map(static fn (Field $f): Field => $f->shared()->optional(), [
                    Field::line('name', 'Hébergeur'),
                    Field::line('address', 'Adresse'),
                    Field::line('phone', 'Téléphone'),
                ])),
                Field::group('publisher', 'Libellés « Éditeur »', [
                    Field::line('title', 'Titre'),
                    Field::group('labels', 'Libellés', [
                        Field::line('name', 'Raison sociale'),
                        Field::line('legalForm', 'Forme juridique'),
                        Field::line('capital', 'Capital social'),
                        Field::line('rcs', 'RCS'),
                        Field::line('siret', 'SIRET'),
                        Field::line('vat', 'TVA'),
                        Field::line('director', 'Directeur de la publication'),
                        Field::line('address', 'Siège'),
                        Field::line('phone', 'Téléphone'),
                        Field::line('email', 'E-mail'),
                    ]),
                ]),
                Field::group('host', 'Libellés « Hébergement »', [
                    Field::line('title', 'Titre'),
                    Field::group('labels', 'Libellés', [
                        Field::line('name', 'Hébergeur'),
                        Field::line('address', 'Adresse'),
                        Field::line('phone', 'Téléphone'),
                    ]),
                ]),
                Field::section('Autres rubriques', [self::legalSections()]),
            ]),
            new PageDefinition('privacy', 'Politique de confidentialité', self::LEGAL, 'Données collectées, finalités, durée de conservation, droits. À tenir à jour à chaque nouveau traitement.', '/privacy-policy/', [
                Field::section('En-tête', [
                    ...self::seo(),
                    Field::line('title', 'Titre'),
                    Field::line('updated', 'Date de mise à jour'),
                ]),
                Field::section('Rubriques', [
                    self::legalSections()->hint('{email} et {address} sont remplacés par l’e-mail des Réglages et le siège de l’éditeur.'),
                ]),
            ]),
        ];

        $byId = [];
        foreach ($pages as $page) {
            $byId[$page->id] = $page;
        }

        return $byId;
    }

    /** @return list<Field> */
    private static function seo(): array
    {
        return [
            Field::line('seoTitle', 'Titre pour Google')->hint('Onglet du navigateur et résultats de recherche.'),
            Field::text('seoDescription', 'Description pour Google', 2)->hint('Environ 150 caractères.'),
        ];
    }

    private static function hero(): Field
    {
        return Field::group('hero', 'En-tête', [
            ...self::seo(),
            Field::line('eyebrow', 'Surtitre'),
            Field::line('title', 'Titre'),
            Field::text('lead', 'Chapeau'),
        ]);
    }

    /** @return list<Field> étiquette, titre, introduction d'une section */
    private static function chapter(): array
    {
        return [
            Field::line('label', 'Étiquette'),
            Field::line('title', 'Titre'),
            Field::text('intro', 'Introduction'),
        ];
    }

    private static function items(string $label, string $key = 'items'): Field
    {
        return Field::list($key, $label, [
            Field::line('title', 'Titre'),
            Field::text('description', 'Description'),
        ]);
    }

    private static function legalSections(): Field
    {
        return Field::list('sections', 'Rubriques', [
            Field::line('title', 'Titre'),
            Field::lines('paragraphs', 'Paragraphes', 5)->optional()->hint('Un paragraphe par ligne.'),
            Field::lines('items', 'Liste à puces', 4)->optional()->hint('Un point par ligne.'),
        ]);
    }
}
