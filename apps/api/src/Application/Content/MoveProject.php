<?php

declare(strict_types=1);

namespace App\Application\Content;

use App\Domain\Content\ProjectRepository;
use Psr\Clock\ClockInterface;

/** Monte ou descend un projet d'un rang dans le portfolio (positions renumérotées 1…n). */
final readonly class MoveProject
{
    public function __construct(
        private ProjectRepository $projects,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(string $id, int $offset, string $actor): void
    {
        $projects = $this->projects->all();
        $index = null;
        foreach ($projects as $i => $project) {
            if ($project->id() === $id) {
                $index = $i;
            }
        }
        $target = null === $index ? null : $index + $offset;
        if (null === $index || null === $target || !isset($projects[$target])) {
            return;
        }
        [$projects[$index], $projects[$target]] = [$projects[$target], $projects[$index]];

        $now = $this->clock->now();
        foreach ($projects as $i => $project) {
            if ($project->position() !== $i + 1) {
                $project->moveTo($i + 1, $now, $actor);
                $this->projects->save($project);
            }
        }
    }
}
