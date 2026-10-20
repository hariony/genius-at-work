<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Content\Service;
use App\Domain\Content\ServiceRepository;
use App\Domain\Content\ServiceSlug;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineServiceRepository implements ServiceRepository
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function all(): array
    {
        $services = [];
        foreach (ServiceSlug::cases() as $slug) {
            $service = $this->get($slug);
            if (null !== $service) {
                $services[] = $service;
            }
        }

        return $services;
    }

    public function get(ServiceSlug $slug): ?Service
    {
        return $this->em->find(Service::class, $slug->value);
    }

    public function save(Service $service): void
    {
        $this->em->persist($service);
        $this->em->flush();
    }
}
