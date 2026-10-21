<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

// Les tests n'utilisent jamais une vraie base, file de messages ou boîte d'envoi, même lancés
// dans le conteneur de dev (dont les variables d'environnement pointent sur MySQL et
// Mailpit, et passeraient sinon avant .env.test) : le schéma est supprimé à chaque test.
// TEST_DATABASE_URL : une base de test dédiée sur un vrai serveur (MariaDB de la production),
// à la place de SQLite. Jamais une base qui contient des données : elle est vidée à chaque test.
foreach ([
    'DATABASE_URL' => getenv('TEST_DATABASE_URL') ?: 'sqlite:///%kernel.project_dir%/var/test.db',
    'MESSENGER_TRANSPORT_DSN' => 'in-memory://',
    'MAILER_DSN' => 'null://null',
] as $name => $value) {
    $_SERVER[$name] = $_ENV[$name] = $value;
    putenv($name.'='.$value);
}

new Dotenv()->bootEnv(dirname(__DIR__).'/.env');

if ((bool) ($_SERVER['APP_DEBUG'] ?? false)) {
    umask(0000);
}

// Base SQLite de test recréée à chaque exécution (schéma toujours aligné sur le mapping).
@unlink(dirname(__DIR__).'/var/test.db');
