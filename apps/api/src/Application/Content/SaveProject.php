<?php

declare(strict_types=1);

namespace App\Application\Content;

use App\Domain\Content\Project;
use App\Domain\Content\ProjectDetails;
use App\Domain\Content\ProjectRepository;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Ulid;

/** Crée (id null) ou modifie un projet. Un nouveau projet se place en fin de portfolio. */
final readonly class SaveProject
{
    public function __construct(
        private ProjectRepository $projects,
        private ClockInterface $clock,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(?string $id, ProjectDetails $details, string $actor): Project
    {
        $now = $this->clock->now();
        $existing = $this->projects->bySlug($details->slug);
        if (null !== $existing && $existing->id() !== $id) {
            throw new \DomainException(\sprintf('L’adresse « %s » est déjà utilisée par « %s ».', $details->slug, $existing->title()));
        }

        if (null === $id) {
            $positions = array_map(static fn (Project $p): int => $p->position(), $this->projects->all());
            $project = new Project((string) new Ulid(), $details, [] === $positions ? 1 : max($positions) + 1, $now, $actor);
        } else {
            $project = $this->projects->get($id) ?? throw new \DomainException('Projet introuvable.');
            $project->update($details, $now, $actor);
        }
        $this->projects->save($project);
        $this->logger->info('Projet enregistré.', ['slug' => $details->slug, 'actor' => $actor]);

        return $project;
    }
}
