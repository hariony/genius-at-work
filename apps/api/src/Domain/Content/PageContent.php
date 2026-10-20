<?php

declare(strict_types=1);

namespace App\Domain\Content;

/**
 * Textes d'une page (ou d'un bloc partagé), dans chaque langue. Leur forme est décrite et
 * validée par le catalogue des pages (App\Application\Content\Page\PageCatalog).
 */
final readonly class PageContent
{
    /**
     * @param array<string, mixed> $fr
     * @param array<string, mixed> $en
     */
    public function __construct(
        public string $id,
        public array $fr,
        public array $en,
        public \DateTimeImmutable $updatedAt,
        public string $updatedBy,
    ) {
    }
}
