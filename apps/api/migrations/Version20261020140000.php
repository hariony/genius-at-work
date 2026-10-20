<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * L'agence a livré plus de 115 projets (15 seulement sont publiés dans le portfolio) :
 * chiffre clé de l'accueil et chapeau de la page Technologie. Chaque texte n'est remplacé que
 * s'il a encore sa valeur d'origine : une modification faite dans le back-office est conservée.
 */
final class Version20261020140000 extends AbstractMigration
{
    private const array LEADS = [
        'fr' => ['Une stack moderne, découpée en couches indépendantes, et quinze projets livrés avec des technologies éprouvées.', 'Une stack moderne, découpée en couches indépendantes, et plus de 115 projets livrés avec des technologies éprouvées.'],
        'en' => ['A modern stack, split into independent layers, and fifteen projects shipped with proven technologies.', 'A modern stack, split into independent layers, and over 115 projects shipped with proven technologies.'],
    ];

    private const array STATS = [
        'fr' => ['projets publiés', 'projets livrés'],
        'en' => ['published projects', 'projects delivered'],
    ];

    public function getDescription(): string
    {
        return 'Plus de 115 projets livrés (accueil, page Technologie)';
    }

    public function up(Schema $schema): void
    {
        foreach (self::LEADS as $locale => [$old, $new]) {
            $this->addSql(
                "UPDATE content_document SET data = JSON_SET(data, '$.{$locale}.hero.lead', :new) WHERE id = 'page:technology-page' AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.{$locale}.hero.lead')) = :old",
                ['old' => $old, 'new' => $new],
            );
        }
        foreach (self::STATS as $locale => [$old, $new]) {
            $stat = "$.{$locale}.sections.manifesto.stats[2]";
            $this->addSql(
                "UPDATE content_document SET data = JSON_SET(data, '{$stat}.value', 115, '{$stat}.suffix', '+', '{$stat}.label', :new) WHERE id = 'page:home' AND JSON_UNQUOTE(JSON_EXTRACT(data, '{$stat}.label')) = :old AND JSON_EXTRACT(data, '{$stat}.value') = 15",
                ['old' => $old, 'new' => $new],
            );
        }
    }

    public function down(Schema $schema): void
    {
        // Correction de contenu : pas de retour arrière (le pipeline ne lance que des migrations « up »).
        $this->throwIrreversibleMigrationException();
    }
}
