<?php

declare(strict_types=1);

namespace Ledger\Infrastructure\Projection;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

final class WalletBalanceSchema
{
    public const TABLE = 'wallet_balances';

    public static function configure(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);

        $table->addColumn('wallet_id', Types::STRING, ['length' => 36]);
        $table->addColumn('currency', Types::STRING, ['length' => 3]);
        $table->addColumn('minor_units', Types::BIGINT);

        $table->setPrimaryKey(['wallet_id']);
    }
}
