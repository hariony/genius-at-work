<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Double authentification du back-office : clé TOTP de chaque compte (vide tant qu'elle n'est pas activée). */
final class Version20261021220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Back-office : clé de double authentification';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE admin_user ADD totp_secret VARCHAR(64) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Jamais exécuté automatiquement : le pipeline de déploiement ne lance que des migrations « up ».
        $this->addSql('ALTER TABLE admin_user DROP totp_secret');
    }

    public function isTransactional(): bool
    {
        // ALTER TABLE valide implicitement la transaction sous MySQL.
        return false;
    }
}
