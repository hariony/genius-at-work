<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Comptes du back-office (créés en ligne de commande : app:admin:create). */
final class Version20261020064846 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Comptes administrateurs du back-office';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE admin_user (email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, id VARCHAR(26) NOT NULL, UNIQUE INDEX uniq_admin_user_email (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // Jamais exécuté automatiquement : le pipeline de déploiement ne lance que des migrations « up ».
        $this->addSql('DROP TABLE admin_user');
    }

    public function isTransactional(): bool
    {
        // CREATE TABLE valide implicitement la transaction sous MySQL.
        return false;
    }
}
