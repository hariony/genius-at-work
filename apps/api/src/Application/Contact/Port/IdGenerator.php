<?php

declare(strict_types=1);

namespace App\Application\Contact\Port;

use App\Domain\Lead\LeadId;

interface IdGenerator
{
    public function nextLeadId(): LeadId;
}
