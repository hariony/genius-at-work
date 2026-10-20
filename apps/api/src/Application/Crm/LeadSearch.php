<?php

declare(strict_types=1);

namespace App\Application\Crm;

use App\Domain\Lead\LeadStatus;
use App\Domain\Lead\ProjectType;

/** Critères de la liste des leads : texte libre sur la référence, le nom, l'e-mail ou l'entreprise. */
final readonly class LeadSearch
{
    public const int PER_PAGE = 25;

    public function __construct(
        public ?LeadStatus $status = null,
        public ?ProjectType $projectType = null,
        public ?string $query = null,
        public int $page = 1,
        public int $perPage = self::PER_PAGE,
    ) {
    }
}
