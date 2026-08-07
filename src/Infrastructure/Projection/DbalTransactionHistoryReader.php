<?php

declare(strict_types=1);

namespace Ledger\Infrastructure\Projection;

use Doctrine\DBAL\Connection;
use Ledger\Application\Wallet\Query\TransactionHistoryReader;
use Ledger\Application\Wallet\Query\WalletTransaction;

final class DbalTransactionHistoryReader implements TransactionHistoryReader
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function historyOf(string $walletId, int $limit = 50): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT kind, minor_units, currency, recorded_at FROM '.WalletTransactionSchema::TABLE
            .' WHERE wallet_id = ? ORDER BY id DESC LIMIT '.max(1, $limit),
            [$walletId],
        );

        return array_map(
            fn (array $row): WalletTransaction => new WalletTransaction(
                $this->column($row, 'kind'),
                (int) $this->column($row, 'minor_units'),
                $this->column($row, 'currency'),
                $this->column($row, 'recorded_at'),
            ),
            $rows,
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function column(array $row, string $name): string
    {
        $value = $row[$name] ?? null;

        if (!is_scalar($value)) {
            throw new \RuntimeException(sprintf('Column "%s" of the transaction projection is not readable', $name));
        }

        return (string) $value;
    }
}
