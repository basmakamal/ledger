<?php

declare(strict_types=1);

namespace Ledger\Infrastructure\EventStore;

use Ledger\Domain\Shared\DomainEvent;

/**
 * Maps the stable event names found in storage onto the classes that can
 * rehydrate them. Streams outlive class names, so the mapping is explicit.
 */
final class EventTypeMap
{
    /**
     * @param array<string, class-string<DomainEvent>> $map
     */
    public function __construct(private readonly array $map)
    {
    }

    /**
     * @return class-string<DomainEvent>
     */
    public function classFor(string $eventType): string
    {
        return $this->map[$eventType]
            ?? throw new \RuntimeException(sprintf('No class registered for event type "%s"', $eventType));
    }

    /**
     * @param array<string, class-string<DomainEvent>> $map
     */
    public function with(array $map): self
    {
        return new self([...$this->map, ...$map]);
    }
}
