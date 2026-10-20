<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Schéma initial : leads (CRM) et file Messenger (transport async local et messages en échec).
 * Table préfixée crm_ : « lead » est un mot réservé de MySQL 8 (fonction LEAD).
 */
final class Version20261019164649 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Leads et file Messenger';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE crm_lead (reference VARCHAR(20) NOT NULL, full_name VARCHAR(120) NOT NULL, email VARCHAR(180) NOT NULL, phone VARCHAR(30) DEFAULT NULL, company VARCHAR(120) DEFAULT NULL, project_type VARCHAR(32) NOT NULL, budget_range VARCHAR(16) DEFAULT NULL, timeline VARCHAR(16) DEFAULT NULL, message LONGTEXT NOT NULL, locale VARCHAR(2) NOT NULL, source_url VARCHAR(500) DEFAULT NULL, status VARCHAR(16) NOT NULL, consent_at DATETIME NOT NULL, created_at DATETIME NOT NULL, last_contact_at DATETIME NOT NULL, anonymized_at DATETIME DEFAULT NULL, id VARCHAR(26) NOT NULL, INDEX idx_lead_status_created (status, created_at), INDEX idx_lead_email (email), INDEX idx_lead_last_contact (last_contact_at), UNIQUE INDEX uniq_lead_reference (reference), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // Jamais exécuté automatiquement : le pipeline de déploiement ne lance que des migrations « up ».
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('DROP TABLE crm_lead');
    }
}
