<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Avec l'offre IA, l'agence compte sept métiers : titre et description de la page Services,
 * introduction de la liste des services sur l'accueil. Chaque texte n'est remplacé que s'il a
 * encore sa valeur d'origine : une modification faite dans le back-office est conservée.
 */
final class Version20261020160000 extends AbstractMigration
{
    /** @var list<array{string, string, string, string}> page, chemin JSON, ancien texte, nouveau texte */
    private const array TEXTS = [
        ['home', '$.fr.sections.services.intro', 'Six métiers, une même exigence : du site vitrine au logiciel métier, de la conception à l’hébergement.', 'Sept métiers, une même exigence : du site vitrine à l’IA, de la conception à l’hébergement.'],
        ['home', '$.en.sections.services.intro', 'Six disciplines, one standard: from showcase sites to business software, from design to hosting.', 'Seven disciplines, one standard: from showcase sites to AI, from design to hosting.'],
        ['services-page', '$.fr.hero.seoDescription', 'Sites internet, applications mobiles et web, logiciels métier, e-commerce, maintenance et hébergement : les six métiers de Genius At Work.', 'Sites internet, applications mobiles et web, logiciels métier, IA, e-commerce, maintenance et hébergement : les sept métiers de Genius At Work.'],
        ['services-page', '$.fr.hero.title', 'Six métiers, une même équipe.', 'Sept métiers, une même équipe.'],
        ['services-page', '$.en.hero.seoDescription', 'Websites, mobile and web apps, business software, e-commerce, maintenance and hosting: the six disciplines of Genius At Work.', 'Websites, mobile and web apps, business software, AI, e-commerce, maintenance and hosting: the seven disciplines of Genius At Work.'],
        ['services-page', '$.en.hero.title', 'Six disciplines, one team.', 'Seven disciplines, one team.'],
    ];

    public function getDescription(): string
    {
        return 'Sept métiers avec l\'IA (page Services, accueil)';
    }

    public function up(Schema $schema): void
    {
        foreach (self::TEXTS as [$page, $path, $old, $new]) {
            $this->addSql(
                "UPDATE content_document SET data = JSON_SET(data, '{$path}', :new) WHERE id = :id AND JSON_UNQUOTE(JSON_EXTRACT(data, '{$path}')) = :old",
                ['id' => 'page:'.$page, 'old' => $old, 'new' => $new],
            );
        }
    }

    public function down(Schema $schema): void
    {
        // Correction de contenu : pas de retour arrière (le pipeline ne lance que des migrations « up »).
        $this->throwIrreversibleMigrationException();
    }
}
