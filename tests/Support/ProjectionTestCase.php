<?php

declare(strict_types=1);

namespace Ledger\Tests\Support;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Tools\DsnParser;
use PHPUnit\Framework\TestCase;

/**
 * Projections are SQL, so they are tested against a real engine: SQLite by
 * default, PostgreSQL when CI points EVENT_STORE_DSN at it.
 */
abstract class ProjectionTestCase extends TestCase
{
    private const SCHEMES = [
        'sqlite' => 'pdo_sqlite',
        'postgres' => 'pdo_pgsql',
        'postgresql' => 'pdo_pgsql',
    ];

    protected Connection $connection;

    protected function setUp(): void
    {
        $dsn = getenv('EVENT_STORE_DSN');

        $this->connection = DriverManager::getConnection(
            (new DsnParser(self::SCHEMES))->parse(false === $dsn || '' === $dsn ? 'sqlite:///:memory:' : $dsn)
        );

        foreach ($this->tables() as $table) {
            $this->connection->executeStatement('DROP TABLE IF EXISTS '.$table);
        }

        $schema = new Schema();
        $this->configureSchema($schema);

        foreach ($schema->toSql($this->connection->getDatabasePlatform()) as $sql) {
            $this->connection->executeStatement($sql);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->tables() as $table) {
            $this->connection->executeStatement('DROP TABLE IF EXISTS '.$table);
        }

        $this->connection->close();
    }

    abstract protected function configureSchema(Schema $schema): void;

    /**
     * @return list<string>
     */
    abstract protected function tables(): array;
}
