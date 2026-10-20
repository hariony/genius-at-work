<?php

declare(strict_types=1);

namespace App\Application\Content;

use App\Domain\Content\ProjectRepository;
use Psr\Log\LoggerInterface;

/** Suppression demandée depuis le back-office, après confirmation. L'image reste dans la médiathèque. */
final readonly class DeleteProject
{
    public function __construct(
        private ProjectRepository $projects,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(string $id, string $actor): void
    {
        $project = $this->projects->get($id);
        if (null === $project) {
            return;
        }
        $this->projects->remove($project);
        $this->logger->notice('Projet supprimé.', ['slug' => $project->slug(), 'actor' => $actor]);
    }
}
