<?php

declare(strict_types=1);

namespace App\Domain\Content;

interface ServiceRepository
{
    /** @return list<Service> dans l'ordre de ServiceSlug */
    public function all(): array;

    public function get(ServiceSlug $slug): ?Service;

    public function save(Service $service): void;
}
