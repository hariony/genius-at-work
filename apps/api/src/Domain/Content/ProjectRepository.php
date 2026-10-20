<?php

declare(strict_types=1);

namespace App\Domain\Content;

interface ProjectRepository
{
    /** @return list<Project> dans l'ordre d'affichage */
    public function all(): array;

    public function get(string $id): ?Project;

    public function bySlug(string $slug): ?Project;

    public function save(Project $project): void;

    public function remove(Project $project): void;

    public function count(): int;
}
