<?php

declare(strict_types=1);

namespace App\Application\Crm\ChangeLeadStatus;

use App\Domain\Lead\LeadStatus;

final readonly class ChangeLeadStatusCommand
{
    /** @param string $actor identifiant de l'administrateur, pour le journal */
    public function __construct(
        public string $leadId,
        public LeadStatus $status,
        public string $actor,
    ) {
    }
}
