<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Contenu éditorial du site, édité dans le back-office (un document JSON par contenu). */
final class Version20261020071534 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Documents de contenu du site';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE content_document (data JSON NOT NULL, updated_at DATETIME NOT NULL, updated_by VARCHAR(180) NOT NULL, id VARCHAR(40) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // Jamais exécuté automatiquement : le pipeline de déploiement ne lance que des migrations « up ».
        $this->addSql('DROP TABLE content_document');
    }

    public function isTransactional(): bool
    {
        // CREATE TABLE valide implicitement la transaction sous MySQL.
        return false;
    }
}
