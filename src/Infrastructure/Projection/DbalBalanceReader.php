<?php

declare(strict_types=1);

namespace Ledger\Infrastructure\Projection;

use Doctrine\DBAL\Connection;
use Ledger\Application\Wallet\Query\BalanceReader;
use Ledger\Application\Wallet\Query\WalletBalance;

final class DbalBalanceReader implements BalanceReader
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function balanceOf(string $walletId): ?WalletBalance
    {
        $row = $this->connection->fetchAssociative(
            'SELECT wallet_id, minor_units, currency FROM '.WalletBalanceSchema::TABLE.' WHERE wallet_id = ?',
            [$walletId],
        );

        return false === $row ? null : $this->toBalance($row);
    }

    public function all(): array
    {
        return array_map(
            fn (array $row): WalletBalance => $this->toBalance($row),
            $this->connection->fetchAllAssociative(
                'SELECT wallet_id, minor_units, currency FROM '.WalletBalanceSchema::TABLE.' ORDER BY wallet_id',
            ),
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function toBalance(array $row): WalletBalance
    {
        $walletId = $row['wallet_id'];
        $minorUnits = $row['minor_units'];
        $currency = $row['currency'];

        if (!is_scalar($walletId) || !is_scalar($minorUnits) || !is_scalar($currency)) {
            throw new \RuntimeException('The balance projection returned an unreadable row');
        }

        return new WalletBalance((string) $walletId, (int) $minorUnits, (string) $currency);
    }
}
