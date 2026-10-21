<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Nouveau hero d'accueil : verbes qui tournent dans le titre, disciplines au-dessus des couches
 * de l'animation, bandeau du bas. Ajoutés seulement s'ils manquent : aucun texte déjà saisi
 * n'est modifié.
 */
final class Version20261021090000 extends AbstractMigration
{
    /** @var list<array{string, string}> chemin JSON, valeur JSON */
    private const array VALUES = [
        ['$.fr.hero.verbs', '["construisons", "déployons", "maintenons", "concevons"]'],
        ['$.fr.hero.visual.layers', '[{"discipline": "Design", "layer": "Interface"}, {"discipline": "Technologie", "layer": "API"}, {"discipline": "IA", "layer": "Modèles"}, {"discipline": "Ingénierie", "layer": "Données"}]'],
        ['$.fr.hero.strip', '{"services": "{n} métiers", "featured": "À la une"}'],
        ['$.en.hero.verbs', '["build", "ship", "maintain", "design"]'],
        ['$.en.hero.visual.layers', '[{"discipline": "Design", "layer": "Interface"}, {"discipline": "Technology", "layer": "API"}, {"discipline": "AI", "layer": "Models"}, {"discipline": "Engineering", "layer": "Data"}]'],
        ['$.en.hero.strip', '{"services": "{n} disciplines", "featured": "Featured"}'],
    ];

    public function getDescription(): string
    {
        return 'Hero d’accueil : verbes, disciplines, bandeau';
    }

    public function up(Schema $schema): void
    {
        // JSON_EXTRACT(…, '$') : la valeur est lue comme du JSON par MySQL comme par MariaDB
        // (qui ne connaît pas CAST(… AS JSON)).
        foreach (self::VALUES as [$path, $value]) {
            $this->addSql(
                "UPDATE content_document SET data = JSON_SET(data, '{$path}', JSON_EXTRACT(:value, '$')) WHERE id = 'page:home' AND JSON_EXTRACT(data, '{$path}') IS NULL",
                ['value' => $value],
            );
        }
    }

    public function down(Schema $schema): void
    {
        // Jamais exécuté automatiquement : le pipeline de déploiement ne lance que des migrations « up ».
        foreach (self::VALUES as [$path]) {
            $this->addSql("UPDATE content_document SET data = JSON_REMOVE(data, '{$path}') WHERE id = 'page:home'");
        }
    }
}
