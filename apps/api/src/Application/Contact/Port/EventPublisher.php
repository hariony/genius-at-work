<?php

declare(strict_types=1);

namespace App\Application\Contact\Port;

interface EventPublisher
{
    public function publish(object ...$events): void;
}
