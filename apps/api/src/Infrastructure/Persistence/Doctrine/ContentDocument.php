<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

/**
 * Un document de contenu (réglages, textes d'une page…) stocké en JSON, avec sa dernière
 * modification. La validation est faite par le domaine avant l'enregistrement.
 */
class ContentDocument
{
    /** @param array<string, mixed> $data */
    public function __construct(
        private string $id,
        private array $data,
        private \DateTimeImmutable $updatedAt,
        private string $updatedBy,
    ) {
    }

    /** @param array<string, mixed> $data */
    public function replace(array $data, string $actor, \DateTimeImmutable $now): void
    {
        $this->data = $data;
        $this->updatedAt = $now;
        $this->updatedBy = $actor;
    }

    public function id(): string
    {
        return $this->id;
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        return $this->data;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function updatedBy(): string
    {
        return $this->updatedBy;
    }
}
