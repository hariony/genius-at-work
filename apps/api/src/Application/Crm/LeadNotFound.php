<?php

declare(strict_types=1);

namespace App\Application\Crm;

final class LeadNotFound extends \RuntimeException
{
    public static function withId(string $id): self
    {
        return new self(\sprintf('Lead introuvable : %s.', $id));
    }
}
