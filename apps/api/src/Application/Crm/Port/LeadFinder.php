<?php

declare(strict_types=1);

namespace App\Application\Crm\Port;

use App\Application\Crm\LeadPage;
use App\Application\Crm\LeadSearch;

/** Lecture des leads pour le back-office (listes, filtres, compteurs). */
interface LeadFinder
{
    public function search(LeadSearch $search): LeadPage;

    /** @return array<string, int> nombre de leads par valeur de LeadStatus */
    public function countByStatus(): array;

    public function countCreatedSince(\DateTimeImmutable $since): int;
}
