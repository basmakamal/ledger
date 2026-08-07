<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Ledger\Infrastructure\Projection\WalletBalanceSchema;

final class Version20260808090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the wallet balance projection';
    }

    public function up(Schema $schema): void
    {
        WalletBalanceSchema::configure($schema);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable(WalletBalanceSchema::TABLE);
    }
}
