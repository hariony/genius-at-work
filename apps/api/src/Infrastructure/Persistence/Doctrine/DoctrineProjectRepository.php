<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Content\Project;
use App\Domain\Content\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineProjectRepository implements ProjectRepository
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function all(): array
    {
        /** @var list<Project> $projects */
        $projects = $this->em->createQueryBuilder()
            ->select('p', 'c')
            ->from(Project::class, 'p')
            ->join('p.cover', 'c')
            ->orderBy('p.position', 'ASC')
            ->getQuery()
            ->getResult();

        return $projects;
    }

    public function get(string $id): ?Project
    {
        return $this->em->find(Project::class, $id);
    }

    public function bySlug(string $slug): ?Project
    {
        return $this->em->getRepository(Project::class)->findOneBy(['slug' => $slug]);
    }

    public function save(Project $project): void
    {
        $this->em->persist($project);
        $this->em->flush();
    }

    public function remove(Project $project): void
    {
        $this->em->remove($project);
        $this->em->flush();
    }

    public function count(): int
    {
        return $this->em->getRepository(Project::class)->count();
    }
}
