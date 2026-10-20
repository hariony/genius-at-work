<?php

declare(strict_types=1);

namespace App\Application\Content;

use App\Application\Content\Page\PageData;
use App\Application\Content\Page\PageDefinition;
use App\Domain\Content\ContentRepository;
use App\Domain\Content\PageContent;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;

/** Enregistre les textes d'une page, déjà normalisés et validés (PageNormalizer). */
final readonly class UpdatePage
{
    public function __construct(
        private ContentRepository $content,
        private ClockInterface $clock,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(PageDefinition $page, PageData $data, string $actor): void
    {
        if (!$data->isValid()) {
            throw new \DomainException(\sprintf('Textes de « %s » incomplets.', $page->title));
        }
        $this->content->savePage(new PageContent($page->id, $data->fr, $data->en, $this->clock->now(), $actor));
        $this->logger->info('Page modifiée.', ['page' => $page->id, 'actor' => $actor]);
    }
}
