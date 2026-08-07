<?php

declare(strict_types=1);

namespace Ledger\Tests\Integration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Tools\DsnParser;
use Ledger\Application\Port\EventStore;
use Ledger\Infrastructure\EventStore\DbalEventStore;
use Ledger\Infrastructure\EventStore\EventStoreSchema;
use Ledger\Infrastructure\EventStore\EventTypeMap;
use Ledger\Tests\Support\EventStoreContractTestCase;
use Ledger\Tests\Support\Fake\CounterIncremented;
use Ledger\Tests\Support\FrozenClock;

/**
 * Runs the same contract as the in-memory store. Locally that happens on
 * SQLite; CI points EVENT_STORE_DSN at PostgreSQL and runs it again.
 */
final class DbalEventStoreTest extends EventStoreContractTestCase
{
    private const SCHEMES = [
        'sqlite' => 'pdo_sqlite',
        'postgres' => 'pdo_pgsql',
        'postgresql' => 'pdo_pgsql',
    ];

    private Connection $connection;

    private DbalEventStore $store;

    protected function setUp(): void
    {
        $dsn = getenv('EVENT_STORE_DSN');
        $this->connection = DriverManager::getConnection(
            (new DsnParser(self::SCHEMES))->parse(false === $dsn || '' === $dsn ? 'sqlite:///:memory:' : $dsn)
        );

        $this->dropTableIfPresent();

        $schema = new Schema();
        EventStoreSchema::configure($schema);

        foreach ($schema->toSql($this->connection->getDatabasePlatform()) as $sql) {
            $this->connection->executeStatement($sql);
        }

        $this->store = new DbalEventStore(
            $this->connection,
            new EventTypeMap([CounterIncremented::eventType() => CounterIncremented::class]),
            new FrozenClock(),
        );
    }

    protected function tearDown(): void
    {
        $this->dropTableIfPresent();
        $this->connection->close();
    }

    public function testUnknownEventTypesFailLoudlyRatherThanSilently(): void
    {
        $this->connection->insert(EventStoreSchema::TABLE, [
            'aggregate_id' => 'c1',
            'version' => 1,
            'event_type' => 'test.retired_event',
            'payload' => '{}',
            'recorded_at' => '2026-01-15 10:00:00',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('test.retired_event');

        $this->store->load('c1');
    }

    public function testPayloadsSurviveTheRoundTrip(): void
    {
        $this->store->append('c1', 0, [new CounterIncremented(-42)]);

        $event = $this->store->load('c1')[0]->event;

        self::assertInstanceOf(CounterIncremented::class, $event);
        self::assertSame(-42, $event->by);
    }

    protected function store(): EventStore
    {
        return $this->store;
    }

    private function dropTableIfPresent(): void
    {
        $this->connection->executeStatement('DROP TABLE IF EXISTS '.EventStoreSchema::TABLE);
    }
}
