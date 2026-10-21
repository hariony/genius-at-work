<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * La section « Ingénierie » quitte l'accueil : ses textes, encore affichés sur la page
 * Technologie, y sont copiés (sans le lien vers cette même page). Copie seulement si elle
 * manque ; les textes de l'accueil restent en base, ignorés : rien n'est supprimé.
 */
final class Version20261021180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Textes de la stack : de l’accueil à la page Technologie';
    }

    public function up(Schema $schema): void
    {
        foreach (['fr', 'en'] as $locale) {
            $this->addSql(
                "UPDATE content_document t JOIN content_document h ON h.id = 'page:home' SET t.data = JSON_SET(t.data, '$.{$locale}.engineering', JSON_REMOVE(JSON_EXTRACT(h.data, '$.{$locale}.sections.engineering'), '$.cta')) WHERE t.id = 'page:technology-page' AND JSON_EXTRACT(t.data, '$.{$locale}.engineering') IS NULL AND JSON_EXTRACT(h.data, '$.{$locale}.sections.engineering.cta') IS NOT NULL",
            );
        }
    }

    public function down(Schema $schema): void
    {
        // Correction de contenu : pas de retour arrière (le pipeline ne lance que des migrations « up »).
        $this->throwIrreversibleMigrationException();
    }
}
