<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Ledger\Infrastructure\EventStore\EventStoreSchema;

final class Version20260807090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the append-only event stream';
    }

    public function up(Schema $schema): void
    {
        EventStoreSchema::configure($schema);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable(EventStoreSchema::TABLE);
    }
}
