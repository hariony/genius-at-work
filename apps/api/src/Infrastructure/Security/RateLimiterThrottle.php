<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Application\Contact\Port\SubmissionThrottle;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/** Deux fenêtres glissantes : 5 envois par heure et 20 par jour, par adresse IP. */
final readonly class RateLimiterThrottle implements SubmissionThrottle
{
    public function __construct(
        #[Target('contact_hourly.limiter')]
        private RateLimiterFactoryInterface $hourly,
        #[Target('contact_daily.limiter')]
        private RateLimiterFactoryInterface $daily,
    ) {
    }

    public function consume(string $clientKey): bool
    {
        $key = hash('sha256', $clientKey);

        return $this->hourly->create($key)->consume()->isAccepted()
            && $this->daily->create($key)->consume()->isAccepted();
    }
}
