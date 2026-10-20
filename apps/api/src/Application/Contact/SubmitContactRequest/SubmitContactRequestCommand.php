<?php

declare(strict_types=1);

namespace App\Application\Contact\SubmitContactRequest;

use App\Domain\Lead\BudgetRange;
use App\Domain\Lead\Locale;
use App\Domain\Lead\ProjectType;
use App\Domain\Lead\Timeline;

/** Demande de contact déjà validée (format) par la couche API. */
final readonly class SubmitContactRequestCommand
{
    public function __construct(
        public ProjectType $projectType,
        public string $message,
        public ?BudgetRange $budgetRange,
        public ?Timeline $timeline,
        public string $fullName,
        public string $email,
        public ?string $phone,
        public ?string $company,
        public Locale $locale,
        public ?string $sourceUrl,
        public string $honeypot,
        /** Horodatage d'affichage du formulaire, en millisecondes. */
        public int $startedAt,
        public string $turnstileToken,
        public ?string $clientIp,
    ) {
    }
}
