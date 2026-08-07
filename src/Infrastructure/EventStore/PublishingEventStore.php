<?php

declare(strict_types=1);

namespace Ledger\Infrastructure\EventStore;

use Ledger\Application\Port\EventPublisher;
use Ledger\Application\Port\EventStore;

/**
 * Publishing is a decorator rather than a repository responsibility: anything
 * that reaches the stream gets announced, whichever aggregate wrote it, and
 * a rejected append announces nothing because it never returns from append().
 */
final class PublishingEventStore implements EventStore
{
    public function __construct(
        private readonly EventStore $inner,
        private readonly EventPublisher $publisher,
    ) {
    }

    public function append(string $aggregateId, int $expectedVersion, array $events): void
    {
        $this->inner->append($aggregateId, $expectedVersion, $events);

        $this->publisher->publish(...$events);
    }

    public function load(string $aggregateId): array
    {
        return $this->inner->load($aggregateId);
    }

    public function readAll(): iterable
    {
        return $this->inner->readAll();
    }
}
