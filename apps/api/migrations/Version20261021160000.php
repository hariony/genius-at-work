<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Teinte imposée d'un projet (facultative : sans elle, le site la tire de la capture). */
final class Version20261021160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Projets : teinte imposée';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE content_project ADD tint VARCHAR(7) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Jamais exécuté automatiquement : le pipeline de déploiement ne lance que des migrations « up ».
        $this->addSql('ALTER TABLE content_project DROP tint');
    }

    public function isTransactional(): bool
    {
        // ALTER TABLE valide implicitement la transaction sous MySQL.
        return false;
    }
}
