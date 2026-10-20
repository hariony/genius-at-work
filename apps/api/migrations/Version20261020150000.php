<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Retrait de la ligne « Déjà livré en production » (section Ingénierie) : son titre (accueil)
 * et sa liste de technologies (Stack technique) ne sont plus affichés. Aucun autre texte n'est touché.
 */
final class Version20261020150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Retrait des technologies « déjà livrées en production »';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE content_document SET data = JSON_REMOVE(data, '$.fr.sections.engineering.delivered', '$.en.sections.engineering.delivered') WHERE id = 'page:home'");
        $this->addSql("UPDATE content_document SET data = JSON_REMOVE(data, '$.fr.portfolio', '$.en.portfolio') WHERE id = 'page:technologies'");
    }

    public function down(Schema $schema): void
    {
        // Textes supprimés : ils ne sont pas restaurés (le pipeline ne lance que des migrations « up »).
        $this->throwIrreversibleMigrationException();
    }
}
