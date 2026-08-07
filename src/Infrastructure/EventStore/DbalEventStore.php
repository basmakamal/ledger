<?php

declare(strict_types=1);

namespace Ledger\Infrastructure\EventStore;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Types\Types;
use Ledger\Application\Exception\ConcurrencyConflict;
use Ledger\Application\Port\Clock;
use Ledger\Application\Port\EventStore;
use Ledger\Application\Port\StoredEvent;

final class DbalEventStore implements EventStore
{
    public function __construct(
        private readonly Connection $connection,
        private readonly EventTypeMap $types,
        private readonly Clock $clock,
    ) {
    }

    public function append(string $aggregateId, int $expectedVersion, array $events): void
    {
        if ([] === $events) {
            return;
        }

        $recordedAt = $this->clock->now();

        try {
            $this->connection->transactional(function (Connection $connection) use ($aggregateId, $expectedVersion, $events, $recordedAt): void {
                $version = $expectedVersion;

                foreach ($events as $event) {
                    $connection->insert(EventStoreSchema::TABLE, [
                        'aggregate_id' => $aggregateId,
                        'version' => ++$version,
                        'event_type' => $event::eventType(),
                        'payload' => json_encode($event->toPayload(), \JSON_THROW_ON_ERROR),
                        'recorded_at' => $recordedAt,
                    ], [
                        'recorded_at' => Types::DATETIME_IMMUTABLE,
                    ]);
                }
            });
        } catch (UniqueConstraintViolationException) {
            throw ConcurrencyConflict::for($aggregateId, $expectedVersion);
        }
    }

    public function load(string $aggregateId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT version, event_type, payload, recorded_at FROM '.EventStoreSchema::TABLE
            .' WHERE aggregate_id = ? ORDER BY version',
            [$aggregateId],
        );

        return array_map(
            fn (array $row): StoredEvent => $this->toStoredEvent($aggregateId, $row),
            $rows,
        );
    }

    public function readAll(): iterable
    {
        $result = $this->connection->executeQuery(
            'SELECT aggregate_id, version, event_type, payload, recorded_at FROM '
            .EventStoreSchema::TABLE.' ORDER BY id',
        );

        while (false !== ($row = $result->fetchAssociative())) {
            yield $this->toStoredEvent($this->column($row, 'aggregate_id'), $row);
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    private function toStoredEvent(string $aggregateId, array $row): StoredEvent
    {
        $class = $this->types->classFor($this->column($row, 'event_type'));

        /** @var array<string, scalar|null> $payload */
        $payload = json_decode($this->column($row, 'payload'), true, 512, \JSON_THROW_ON_ERROR);

        return new StoredEvent(
            $aggregateId,
            (int) $this->column($row, 'version'),
            $class::fromPayload($payload),
            new \DateTimeImmutable($this->column($row, 'recorded_at')),
        );
    }

    /**
     * Column values arrive as mixed; every column this store reads is a
     * scalar, and anything else means the table was tampered with.
     *
     * @param array<string, mixed> $row
     */
    private function column(array $row, string $name): string
    {
        $value = $row[$name] ?? null;

        if (!is_scalar($value)) {
            throw new \RuntimeException(sprintf('Column "%s" of the event stream is not readable', $name));
        }

        return (string) $value;
    }
}
