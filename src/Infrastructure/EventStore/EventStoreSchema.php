<?php

declare(strict_types=1);

namespace Ledger\Infrastructure\EventStore;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

/**
 * Single definition of the stream table, shared by the migration and the
 * integration tests so the two can never describe different schemas.
 */
final class EventStoreSchema
{
    public const TABLE = 'event_stream';

    public static function configure(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);

        // Autoincrement id gives projections a total order to walk; version
        // only orders events inside one stream.
        $table->addColumn('id', Types::BIGINT, ['autoincrement' => true]);
        $table->addColumn('aggregate_id', Types::STRING, ['length' => 36]);
        $table->addColumn('version', Types::INTEGER);
        $table->addColumn('event_type', Types::STRING, ['length' => 100]);
        $table->addColumn('payload', Types::TEXT);
        $table->addColumn('recorded_at', Types::DATETIME_IMMUTABLE);

        $table->setPrimaryKey(['id']);

        // This is the concurrency control: two writers computing the same next
        // version cannot both land, whatever the isolation level.
        $table->addUniqueIndex(['aggregate_id', 'version'], 'uniq_event_stream_version');
    }
}
