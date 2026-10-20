# Back-office

Interface web de l'API Symfony (`apps/api`), sous `/admin` : suivi des demandes de contact et
gestion du contenu du site. Elle réutilise les domaines, dépôts et la base MySQL de l'API : aucun
code métier n'est dupliqué.

| Couche            | Fichiers                                                                                                            |
| ----------------- | ------------------------------------------------------------------------------------------------------------------- |
| Domaine           | `Lead::changeStatus()` (le suivi commercial ne compte pas comme un contact du prospect)                             |
| Cas d'usage       | `Application/Crm` : `ChangeLeadStatus`, `AnonymizeLead`, port de lecture `LeadFinder`                               |
| Infrastructure    | `DoctrineLeadFinder` (recherche, filtres, compteurs), `Security/AdminUser` (comptes, table `admin_user`)            |
| Interface         | `UserInterface/Admin` (contrôleurs, libellés, en-têtes), `templates/admin`, `public/admin/assets` (CSS, police, JS) |
| Ligne de commande | `app:admin:create`                                                                                                  |

## Fonctions

- **Liste** : chiffres clés (à traiter, en cours, gagnées, 30 derniers jours), onglets par statut,
  recherche (référence, nom, e-mail, entreprise ; raccourci `/`), filtre par type de projet, pagination.
- **Fiche** : besoin (type, budget, échéance, message), origine (page, langue, consentement),
  coordonnées avec « Répondre par e-mail », changement de statut.
- **Anonymisation à la demande** (droit à l'effacement) : confirmation en saisissant la référence.
  Efface nom, e-mail, téléphone, entreprise, message et page d'envoi ; garde le type de projet, le
  budget, les dates et la référence. Rien n'est supprimé de la base. L'anonymisation automatique
  après la durée de conservation (`app.lead_retention`, 3 ans) continue de tourner chaque nuit.

Statuts : nouvelle, qualifiée, proposition envoyée, gagnée, perdue, archivée. Chaque changement de
statut et chaque anonymisation sont journalisés avec l'administrateur qui les a faits.

## Contenu du site

Le contenu éditorial est stocké dans MySQL, validé par le domaine `App\Domain\Content`, et édité
dans le back-office : réglages et textes des pages dans `content_document` (un document JSON par
contenu, `settings` et `page:<id>`), projets
dans `content_project`, services dans `content_service`, images dans `content_media`. Le site Astro le
lit sur `GET /api/v1/site-content` :

| Contexte      | Lecture du contenu                                                                |
| ------------- | --------------------------------------------------------------------------------- |
| Développement | à chaque démarrage ; une modification enregistrée recharge la page ouverte (~2 s) |
| Production    | au build (`PUBLIC_API_URL`) ; si l'API ne répond pas, le build échoue             |
| CI (sans API) | `CONTENT_SNAPSHOT` = `apps/api/data/site-content.snapshot.json`                   |

La route ne renvoie que les projets **publiés** ; `version` est une empreinte du contenu publié
(utilisée par le rechargement automatique en développement).

**Images** : envoyées depuis la fiche d'un projet (JPEG, PNG, WebP ou AVIF, 10 Mo et 8000 px
maximum ; le type est vérifié dans le fichier, pas dans son nom). Elles sont enregistrées sous un
nom aléatoire dans `apps/api/public/media` (volume Docker `media-data` en production, jamais
supprimé, ignoré par Git) et servies par l'API sous `/media/…` avec un cache d'un an. Le site les
télécharge au build et les optimise (AVIF, WebP, JPEG). En CI, `apps/api/data` est servi
localement pour fournir les images du contenu initial.

L'instantané est produit par l'API à partir du contenu initial ; un test vérifie qu'il reste
identique (`UPDATE_SNAPSHOT=1 bin/phpunit --filter Snapshot` pour le régénérer).

**Textes des pages** : chaque page est décrite une fois dans
`apps/api/src/Application/Content/Page/PageCatalog.php` (sections, champs FR/EN ou communs aux deux
langues, listes d'éléments). Cette description produit le formulaire du back-office, valide chaque
enregistrement et vérifie le contenu initial (test `PageCatalogTest`). Côté site, la même structure
est typée dans `apps/web/src/types/pages.ts`. Ajouter un texte : un champ dans le catalogue, sa
valeur dans `content-seed.json`, son type côté site, puis son affichage.

**Contenu initial** : `apps/api/data/content-seed.json` (images dans `apps/api/data/media`),
importé par `app:content:import`. La commande n'écrit que les contenus absents (jamais de
remplacement d'une modification) ; les projets ne sont importés qu'une fois, si bien qu'un projet
supprimé dans le back-office ne revient pas. Le script de déploiement la lance après les
migrations.

| Écran           | Contenu                                                                                                | Étape    |
| --------------- | ------------------------------------------------------------------------------------------------------ | -------- |
| Réglages        | nom, e-mail affiché, réseaux sociaux, bureaux (adresse, téléphones, fuseau)                            | en place |
| Projets         | réalisations FR/EN, captures, ordre, brouillon, mise en avant, suppression                             | en place |
| Services        | titres, accroches, introductions, périmètre, technologies, projets liés                                | en place |
| Pages et textes | accueil, À propos, Services, IA & automatisation, Réalisations, Technologie, Contact, page 404         | en place |
| Blocs communs   | bloc IA de l'accueil, méthode et atouts, stack technique, formulaire de contact, menus et pied de page | en place |
| Juridique       | mentions légales (éditeur, hébergeur), politique de confidentialité                                    | en place |

Une modification enregistrée est visible en local immédiatement, et en production après la
publication du site (bouton « Publier », étape à venir ; d'ici là, relancer le workflow
« Publication de contenu »).

## Sécurité

- Comptes créés **uniquement en ligne de commande** ; aucune inscription ni réinitialisation par le web.
- Mot de passe haché (`auto` : bcrypt ou argon2), 12 caractères minimum, saisi masqué.
- Formulaire de connexion et déconnexion protégés par jeton CSRF ; 5 tentatives par 15 minutes.
- Cookie de session `gaw_admin` : `Secure` derrière HTTPS, `HttpOnly`, `SameSite=Strict`.
  L'API publique reste sans état (aucun cookie).
- Pages en `Cache-Control: no-store` et `X-Robots-Tag: noindex` ; CSP dédiée à `/admin`
  (`infra/docker/api/Caddyfile`) : fichiers du back-office uniquement, aucun script en ligne.

## Utilisation en local

```sh
docker compose up -d --wait
docker compose exec api php bin/console doctrine:migrations:migrate -n
docker compose exec api php bin/console app:admin:create vous@genius-at-work.com
```

Puis http://localhost:8088/admin.

## Production

1. Le déploiement applique la migration `admin_user` (migration « up » uniquement).
2. Créer chaque compte sur le serveur :

   ```sh
   docker compose -f compose.prod.yaml exec api php bin/console app:admin:create prenom@genius-at-work.com
   ```

3. Le back-office répond sur `https://api.genius-at-work.com/admin`. Recommandé : dans Cloudflare
   Zero Trust, une application **Access** sur `api.genius-at-work.com/admin*` (code à usage unique
   envoyé aux adresses de l'équipe), en plus du mot de passe.

Retirer un compte : `DELETE FROM admin_user WHERE email = '…'` dans MySQL, à faire à la main.
