<?php

declare(strict_types=1);

namespace App\UserInterface\Api\Resource;

final readonly class ContactRequestCreated
{
    public function __construct(public string $reference)
    {
    }
}
