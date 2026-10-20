<?php

declare(strict_types=1);

namespace App\Domain\Lead;

/** Coordonnées saisies par le prospect : les seules données personnelles d'un lead. */
final readonly class ContactDetails
{
    public function __construct(
        public string $fullName,
        public string $email,
        public ?string $phone = null,
        public ?string $company = null,
    ) {
    }
}
