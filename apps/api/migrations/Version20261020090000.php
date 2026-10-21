<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Accueil : textes de l'animation « sous charge » (légende, invitation, horloge, moments).
 * Ajoutés seulement s'ils manquent : aucun texte déjà saisi n'est modifié.
 */
final class Version20261020090000 extends AbstractMigration
{
    private const string FR = '{"caption": "requêtes simulées", "hint": "Bougez la souris ou touchez : le système encaisse.", "clock": "{time} à {city}", "moments": {"dawn": "aube", "day": "jour", "dusk": "soir", "night": "nuit"}}';
    private const string EN = '{"caption": "simulated requests", "hint": "Move your mouse or tap: the system takes the load.", "clock": "{time} in {city}", "moments": {"dawn": "dawn", "day": "day", "dusk": "evening", "night": "night"}}';

    public function getDescription(): string
    {
        return 'Accueil : textes de l’animation du hero';
    }

    public function up(Schema $schema): void
    {
        // JSON_EXTRACT(…, '$') : la valeur est lue comme du JSON par MySQL comme par MariaDB
        // (qui ne connaît pas CAST(… AS JSON)).
        $this->addSql(
            "UPDATE content_document SET data = JSON_SET(data, '$.fr.hero.visual', JSON_EXTRACT(:fr, '$'), '$.en.hero.visual', JSON_EXTRACT(:en, '$')) WHERE id = 'page:home' AND JSON_EXTRACT(data, '$.fr.hero.visual') IS NULL",
            ['fr' => self::FR, 'en' => self::EN],
        );
    }

    public function down(Schema $schema): void
    {
        // Jamais exécuté automatiquement : le pipeline de déploiement ne lance que des migrations « up ».
        $this->addSql("UPDATE content_document SET data = JSON_REMOVE(data, '$.fr.hero.visual', '$.en.hero.visual') WHERE id = 'page:home'");
    }
}
