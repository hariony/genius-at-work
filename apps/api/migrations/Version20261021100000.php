<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Le formulaire de contact ne demande plus de fourchette de budget : la politique de
 * confidentialité ne le mentionne plus. Chaque texte n'est remplacé que s'il a encore sa valeur
 * d'origine : une modification faite dans le back-office est conservée. Les textes de la
 * question (contact-form.budget) restent en base, ignorés : rien n'est supprimé.
 */
final class Version20261021100000 extends AbstractMigration
{
    /** @var list<array{string, string, string, string}> page, chemin JSON, ancien texte, nouveau texte */
    private const array TEXTS = [
        ['privacy', '$.fr.sections[1].items[2]', 'le contenu de votre demande : type de projet, message, budget et échéance (facultatifs) ;', 'le contenu de votre demande : type de projet, message, échéance (facultative) ;'],
        ['privacy', '$.en.sections[1].items[2]', 'the content of your request: project type, message, budget and timeline (optional);', 'the content of your request: project type, message, timeline (optional);'],
    ];

    public function getDescription(): string
    {
        return 'Formulaire de contact sans budget (politique de confidentialité)';
    }

    public function up(Schema $schema): void
    {
        foreach (self::TEXTS as [$page, $path, $old, $new]) {
            $this->addSql(
                "UPDATE content_document SET data = JSON_SET(data, '{$path}', :new) WHERE id = :id AND JSON_UNQUOTE(JSON_EXTRACT(data, '{$path}')) = :old",
                ['id' => 'page:'.$page, 'old' => $old, 'new' => $new],
            );
        }
    }

    public function down(Schema $schema): void
    {
        // Correction de contenu : pas de retour arrière (le pipeline ne lance que des migrations « up »).
        $this->throwIrreversibleMigrationException();
    }
}
