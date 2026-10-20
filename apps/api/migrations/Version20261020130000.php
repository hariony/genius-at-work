<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Offre IA : entrée « IA » du menu et choix « IA & automatisation » du formulaire de contact.
 * Ajoutés seulement s'ils manquent : aucun texte déjà saisi n'est modifié. La page IA, le bloc
 * IA de l'accueil et le service « ai » arrivent par app:content:import (contenus absents).
 */
final class Version20261020130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Offre IA : entrée de menu et type de projet';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE content_document SET data = JSON_SET(data, '$.fr.nav.ai', 'IA', '$.en.nav.ai', 'AI') WHERE id = 'page:interface' AND JSON_EXTRACT(data, '$.fr.nav.ai') IS NULL");
        $this->addSql("UPDATE content_document SET data = JSON_SET(data, '$.fr.projectType.options.ai', 'IA & automatisation', '$.en.projectType.options.ai', 'AI & automation') WHERE id = 'page:contact-form' AND JSON_EXTRACT(data, '$.fr.projectType.options.ai') IS NULL");
    }

    public function down(Schema $schema): void
    {
        // Jamais exécuté automatiquement : le pipeline de déploiement ne lance que des migrations « up ».
        $this->addSql("UPDATE content_document SET data = JSON_REMOVE(data, '$.fr.nav.ai', '$.en.nav.ai') WHERE id = 'page:interface'");
        $this->addSql("UPDATE content_document SET data = JSON_REMOVE(data, '$.fr.projectType.options.ai', '$.en.projectType.options.ai') WHERE id = 'page:contact-form'");
    }
}
