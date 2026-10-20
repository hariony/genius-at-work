<?php

declare(strict_types=1);

namespace App\Domain\Lead;

/** Aligné sur BUDGET_RANGES de packages/types/src/contact.ts. */
enum BudgetRange: string
{
    case LessThan10k = 'lt-10k';
    case From10kTo30k = '10k-30k';
    case From30kTo80k = '30k-80k';
    case MoreThan80k = 'gt-80k';
    case Undefined = 'undefined';
}
