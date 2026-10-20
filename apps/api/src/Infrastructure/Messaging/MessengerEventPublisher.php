<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging;

use App\Application\Contact\Port\EventPublisher;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class MessengerEventPublisher implements EventPublisher
{
    public function __construct(private MessageBusInterface $bus)
    {
    }

    public function publish(object ...$events): void
    {
        foreach ($events as $event) {
            $this->bus->dispatch($event);
        }
    }
}
