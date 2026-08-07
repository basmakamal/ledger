<?php

declare(strict_types=1);

namespace Ledger\Infrastructure\EventStore;

use Ledger\Application\Exception\ConcurrencyConflict;
use Ledger\Application\Port\Clock;
use Ledger\Application\Port\EventStore;
use Ledger\Application\Port\StoredEvent;

final class InMemoryEventStore implements EventStore
{
    /** @var array<string, list<StoredEvent>> */
    private array $streams = [];

    public function __construct(private readonly Clock $clock)
    {
    }

    public function append(string $aggregateId, int $expectedVersion, array $events): void
    {
        $stream = $this->streams[$aggregateId] ?? [];

        if (\count($stream) !== $expectedVersion) {
            throw ConcurrencyConflict::for($aggregateId, $expectedVersion);
        }

        $version = $expectedVersion;
        $recordedAt = $this->clock->now();

        foreach ($events as $event) {
            $stream[] = new StoredEvent($aggregateId, ++$version, $event, $recordedAt);
        }

        $this->streams[$aggregateId] = $stream;
    }

    public function load(string $aggregateId): array
    {
        return $this->streams[$aggregateId] ?? [];
    }

    public function readAll(): iterable
    {
        foreach ($this->streams as $stream) {
            yield from $stream;
        }
    }
}
