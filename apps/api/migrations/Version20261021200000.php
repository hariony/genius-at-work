<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Nom du bouton flottant « Retour en haut ». Ajouté seulement s'il manque. */
final class Version20261021200000 extends AbstractMigration
{
    /** @var array<string, string> par langue */
    private const array LABELS = ['fr' => 'Retour en haut', 'en' => 'Back to top'];

    public function getDescription(): string
    {
        return 'Bouton « Retour en haut »';
    }

    public function up(Schema $schema): void
    {
        foreach (self::LABELS as $locale => $label) {
            $this->addSql(
                "UPDATE content_document SET data = JSON_SET(data, '$.{$locale}.a11y.backToTop', :label) WHERE id = 'page:interface' AND JSON_EXTRACT(data, '$.{$locale}.a11y.backToTop') IS NULL",
                ['label' => $label],
            );
        }
    }

    public function down(Schema $schema): void
    {
        // Correction de contenu : pas de retour arrière (le pipeline ne lance que des migrations « up »).
        $this->throwIrreversibleMigrationException();
    }
}
