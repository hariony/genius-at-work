<?php

declare(strict_types=1);

namespace App\Domain\Lead;

enum LeadStatus: string
{
    case New = 'new';
    case Qualified = 'qualified';
    case Proposal = 'proposal';
    case Won = 'won';
    case Lost = 'lost';
    case Archived = 'archived';
}
