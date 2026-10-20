<?php

declare(strict_types=1);

namespace App\Application\Content;

use App\Domain\Content\ContentRepository;
use App\Domain\Content\SiteSettings;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;

final readonly class UpdateSettings
{
    public function __construct(
        private ContentRepository $content,
        private ClockInterface $clock,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(SiteSettings $settings, string $actor): void
    {
        $this->content->saveSettings($settings, $actor, $this->clock->now());
        $this->logger->info('Réglages du site modifiés.', ['actor' => $actor]);
    }
}
