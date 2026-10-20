<?php

declare(strict_types=1);

namespace App\Application\Content;

use App\Domain\Content\ServiceDetails;
use App\Domain\Content\ServiceRepository;
use App\Domain\Content\ServiceSlug;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;

final readonly class UpdateService
{
    public function __construct(
        private ServiceRepository $services,
        private ClockInterface $clock,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ServiceSlug $slug, ServiceDetails $details, string $actor): void
    {
        $service = $this->services->get($slug) ?? throw new \DomainException('Service introuvable.');
        $service->update($details, $this->clock->now(), $actor);
        $this->services->save($service);
        $this->logger->info('Service modifié.', ['slug' => $slug->value, 'actor' => $actor]);
    }
}
