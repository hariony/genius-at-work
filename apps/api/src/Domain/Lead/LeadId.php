<?php

declare(strict_types=1);

namespace App\Domain\Lead;

/** Identifiant d'un lead (ULID, 26 caractères). */
final readonly class LeadId implements \Stringable
{
    private function __construct(public string $value)
    {
        if (!preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/', $value)) {
            throw new \InvalidArgumentException('Identifiant de lead invalide.');
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
