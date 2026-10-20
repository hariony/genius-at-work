<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Content\Media;
use App\Domain\Content\MediaRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineMediaRepository implements MediaRepository
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function get(string $id): ?Media
    {
        return $this->em->find(Media::class, $id);
    }

    public function save(Media $media): void
    {
        $this->em->persist($media);
        $this->em->flush();
    }
}
