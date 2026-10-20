<?php

declare(strict_types=1);

namespace App\UserInterface\Cli;

use App\Application\Content\ImportContent;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Import initial du contenu du site. Les contenus déjà présents en base ne sont jamais remplacés.
 * Les images des projets sont lues dans le dossier media/ voisin du fichier.
 *
 * @phpstan-import-type Seed from ImportContent
 */
#[AsCommand(name: 'app:content:import', description: 'Importe le contenu initial du site (contenus absents uniquement).')]
final readonly class ImportContentCommand
{
    public function __construct(private ImportContent $import)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'Fichier JSON du contenu initial')]
        string $file = 'data/content-seed.json',
    ): int {
        $json = @file_get_contents($file);
        if (false === $json) {
            $io->error(\sprintf('Fichier introuvable : %s.', $file));

            return Command::FAILURE;
        }
        /** @var Seed $seed */
        $seed = json_decode($json, true, flags: \JSON_THROW_ON_ERROR);

        foreach (($this->import)($seed, \dirname($file).'/media') as $content => $imported) {
            $io->writeln(\sprintf(' %s %s', $imported ? '✓ importé  ' : '· déjà là ', $content));
        }
        $io->success('Import terminé.');

        return Command::SUCCESS;
    }
}
