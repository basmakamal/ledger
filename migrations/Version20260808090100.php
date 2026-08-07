<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Ledger\Infrastructure\Projection\WalletTransactionSchema;

final class Version20260808090100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the wallet transaction history projection';
    }

    public function up(Schema $schema): void
    {
        WalletTransactionSchema::configure($schema);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable(WalletTransactionSchema::TABLE);
    }
}
