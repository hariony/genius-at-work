<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Projets du portfolio, pages de services et images du back-office. */
final class Version20261020073913 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Projets, services et médiathèque';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE content_media (file_name VARCHAR(80) NOT NULL, mime_type VARCHAR(20) NOT NULL, width INT NOT NULL, height INT NOT NULL, byte_size INT NOT NULL, original_name VARCHAR(255) NOT NULL, uploaded_at DATETIME NOT NULL, id VARCHAR(40) NOT NULL, UNIQUE INDEX uniq_media_file_name (file_name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE content_project (slug VARCHAR(80) NOT NULL, title VARCHAR(160) NOT NULL, client VARCHAR(160) DEFAULT NULL, client_type VARCHAR(16) DEFAULT NULL, period_start SMALLINT DEFAULT NULL, period_end SMALLINT DEFAULT NULL, categories JSON NOT NULL, technologies JSON NOT NULL, url VARCHAR(500) DEFAULT NULL, summary JSON NOT NULL, cover_alt JSON NOT NULL, featured TINYINT NOT NULL, published TINYINT NOT NULL, position INT NOT NULL, updated_at DATETIME NOT NULL, updated_by VARCHAR(180) NOT NULL, id VARCHAR(26) NOT NULL, cover_id VARCHAR(40) NOT NULL, INDEX idx_project_position (position), UNIQUE INDEX uniq_project_slug (slug), INDEX IDX_F0BB095D922726E9 (cover_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE content_service (title JSON NOT NULL, summary JSON NOT NULL, intro JSON NOT NULL, scope JSON NOT NULL, technologies JSON NOT NULL, related_projects JSON NOT NULL, updated_at DATETIME NOT NULL, updated_by VARCHAR(180) NOT NULL, slug VARCHAR(32) NOT NULL, PRIMARY KEY (slug)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE content_project ADD CONSTRAINT FK_F0BB095D922726E9 FOREIGN KEY (cover_id) REFERENCES content_media (id)');
    }

    public function down(Schema $schema): void
    {
        // Jamais exécuté automatiquement : le pipeline de déploiement ne lance que des migrations « up ».
        $this->addSql('ALTER TABLE content_project DROP FOREIGN KEY FK_F0BB095D922726E9');
        $this->addSql('DROP TABLE content_media');
        $this->addSql('DROP TABLE content_project');
        $this->addSql('DROP TABLE content_service');
    }

    public function isTransactional(): bool
    {
        // CREATE TABLE valide implicitement la transaction sous MySQL.
        return false;
    }
}
