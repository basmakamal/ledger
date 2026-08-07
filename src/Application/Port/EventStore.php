<?php

declare(strict_types=1);

namespace Ledger\Application\Port;

use Ledger\Application\Exception\ConcurrencyConflict;
use Ledger\Domain\Shared\DomainEvent;

interface EventStore
{
    /**
     * @param list<DomainEvent> $events
     *
     * @throws ConcurrencyConflict when the stream moved on since it was read
     */
    public function append(string $aggregateId, int $expectedVersion, array $events): void;

    /**
     * @return list<StoredEvent> in version order, empty when the stream does not exist
     */
    public function load(string $aggregateId): array;

    /**
     * Every event ever written, in the order it was written. Projections are
     * rebuilt from this, so it is streamed rather than materialised.
     *
     * @return iterable<StoredEvent>
     */
    public function readAll(): iterable;
}
