<?php

declare(strict_types=1);

namespace Ledger\Infrastructure\Projection;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

final class WalletTransactionSchema
{
    public const TABLE = 'wallet_transactions';

    public static function configure(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);

        $table->addColumn('id', Types::BIGINT, ['autoincrement' => true]);
        $table->addColumn('wallet_id', Types::STRING, ['length' => 36]);
        $table->addColumn('kind', Types::STRING, ['length' => 16]);
        $table->addColumn('minor_units', Types::BIGINT);
        $table->addColumn('currency', Types::STRING, ['length' => 3]);
        $table->addColumn('recorded_at', Types::DATETIME_IMMUTABLE);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['wallet_id'], 'idx_wallet_transactions_wallet');
    }
}
