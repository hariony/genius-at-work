<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * L'agence vend des sites WordPress : WordPress rejoint la couche « Contenu » de la stack
 * technique, et le périmètre du métier « Sites internet ». Ajouts seulement s'ils manquent :
 * rien de ce qui a été saisi dans le back-office n'est modifié ni retiré.
 */
final class Version20261021120000 extends AbstractMigration
{
    /** @var list<array{string, string}> langue, ligne de périmètre */
    private const array SCOPE = [
        ['fr', 'Sites WordPress sur mesure'],
        ['en', 'Custom WordPress websites'],
    ];

    public function getDescription(): string
    {
        return 'WordPress : stack technique et périmètre « Sites internet »';
    }

    public function up(Schema $schema): void
    {
        // Couche « Contenu », où qu'elle soit dans la liste (technologies communes aux deux langues).
        foreach (['fr', 'en'] as $locale) {
            for ($i = 0; $i < 12; ++$i) {
                $path = "$.{$locale}.stack[{$i}]";
                $this->addSql(
                    "UPDATE content_document SET data = JSON_ARRAY_APPEND(data, '{$path}.technologies', 'WordPress') WHERE id = 'page:technologies' AND JSON_UNQUOTE(JSON_EXTRACT(data, '{$path}.layer')) = 'cms' AND NOT JSON_CONTAINS(JSON_EXTRACT(data, '{$path}.technologies'), '\"WordPress\"')",
                );
            }
        }
        foreach (self::SCOPE as [$locale, $line]) {
            $this->addSql(
                "UPDATE content_service SET scope = JSON_ARRAY_APPEND(scope, '$.{$locale}', :line) WHERE slug = 'websites' AND NOT JSON_CONTAINS(JSON_EXTRACT(scope, '$.{$locale}'), JSON_QUOTE(:line))",
                ['line' => $line],
            );
        }
    }

    public function down(Schema $schema): void
    {
        // Correction de contenu : pas de retour arrière (le pipeline ne lance que des migrations « up »).
        $this->throwIrreversibleMigrationException();
    }
}
