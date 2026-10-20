<?php

declare(strict_types=1);

namespace App\UserInterface\Cli;

use App\Application\Privacy\PurgeExpiredLeads;
use App\Application\Privacy\PurgeExpiredLeadsHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/** Exécution manuelle de l'anonymisation (elle tourne aussi chaque nuit via le Scheduler). */
#[AsCommand(name: 'app:leads:purge-expired', description: 'Anonymise les leads au-delà de la durée de conservation.')]
final class PurgeExpiredLeadsCommand
{
    public function __construct(private readonly PurgeExpiredLeadsHandler $handler)
    {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $count = ($this->handler)(new PurgeExpiredLeads());
        $io->success(\sprintf('%d lead(s) anonymisé(s).', $count));

        return Command::SUCCESS;
    }
}
