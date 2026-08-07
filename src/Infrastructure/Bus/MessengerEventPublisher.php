<?php

declare(strict_types=1);

namespace Ledger\Infrastructure\Bus;

use Ledger\Application\Port\EventPublisher;
use Ledger\Domain\Shared\DomainEvent;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerEventPublisher implements EventPublisher
{
    public function __construct(private readonly MessageBusInterface $eventBus)
    {
    }

    public function publish(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
