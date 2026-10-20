<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Contact\Port\IdGenerator;
use App\Domain\Lead\LeadId;
use Symfony\Component\Uid\Ulid;

final class UlidGenerator implements IdGenerator
{
    public function nextLeadId(): LeadId
    {
        return LeadId::fromString((string) new Ulid());
    }
}
