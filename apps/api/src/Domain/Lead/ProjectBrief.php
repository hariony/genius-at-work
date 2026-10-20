<?php

declare(strict_types=1);

namespace App\Domain\Lead;

final readonly class ProjectBrief
{
    public function __construct(
        public ProjectType $type,
        public string $message,
        public ?BudgetRange $budget = null,
        public ?Timeline $timeline = null,
    ) {
    }
}
