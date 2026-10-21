<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Les noms des catégories de projet deviennent des textes du back-office (page « Réalisations »).
 * Ajout seulement s'ils manquent : rien de ce qui a été saisi n'est modifié.
 */
final class Version20261021140000 extends AbstractMigration
{
    /** @var array<string, array<string, string>> par langue, par catégorie */
    private const array LABELS = [
        'fr' => [
            'web-app' => 'App web',
            'mobile' => 'Mobile',
            'ux-ui' => 'Design UX/UI',
            'e-commerce' => 'E-commerce',
            'innovation' => 'Innovation',
            'showcase' => 'Site vitrine',
            'travel' => 'Voyage',
            'ai' => 'IA',
        ],
        'en' => [
            'web-app' => 'Web app',
            'mobile' => 'Mobile',
            'ux-ui' => 'UX/UI design',
            'e-commerce' => 'E-commerce',
            'innovation' => 'Innovation',
            'showcase' => 'Showcase site',
            'travel' => 'Travel',
            'ai' => 'AI',
        ],
    ];

    public function getDescription(): string
    {
        return 'Noms des catégories de projet modifiables dans le back-office';
    }

    public function up(Schema $schema): void
    {
        foreach (self::LABELS as $locale => $labels) {
            $this->addSql(
                "UPDATE content_document SET data = JSON_SET(data, '$.{$locale}.categoryLabels', CAST(:labels AS JSON)) WHERE id = 'page:projects-page' AND JSON_EXTRACT(data, '$.{$locale}.categoryLabels') IS NULL",
                ['labels' => json_encode($labels, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE)],
            );
        }
    }

    public function down(Schema $schema): void
    {
        // Correction de contenu : pas de retour arrière (le pipeline ne lance que des migrations « up »).
        $this->throwIrreversibleMigrationException();
    }
}
