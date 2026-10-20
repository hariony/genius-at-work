<?php

declare(strict_types=1);

namespace App\Application\Crm;

use App\Domain\Lead\Lead;

final readonly class LeadPage
{
    /** @param list<Lead> $items */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {
    }

    public function pageCount(): int
    {
        return max(1, (int) ceil($this->total / $this->perPage));
    }
}
