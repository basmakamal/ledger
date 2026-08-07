<?php

declare(strict_types=1);

namespace Ledger\Infrastructure\Projection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Ledger\Application\Port\Clock;
use Ledger\Domain\Wallet\Event\FundsDeposited;
use Ledger\Domain\Wallet\Event\FundsWithdrawn;

final class WalletTransactionProjector
{
    public const DEPOSIT = 'deposit';
    public const WITHDRAWAL = 'withdrawal';

    public function __construct(
        private readonly Connection $connection,
        private readonly Clock $clock,
    ) {
    }

    public function whenFundsDeposited(FundsDeposited $event): void
    {
        $this->record($event->walletId, self::DEPOSIT, $event->minorUnits, $event->currency);
    }

    public function whenFundsWithdrawn(FundsWithdrawn $event): void
    {
        $this->record($event->walletId, self::WITHDRAWAL, $event->minorUnits, $event->currency);
    }

    public function reset(): void
    {
        $this->connection->executeStatement('DELETE FROM '.WalletTransactionSchema::TABLE);
    }

    private function record(string $walletId, string $kind, int $minorUnits, string $currency): void
    {
        $this->connection->insert(WalletTransactionSchema::TABLE, [
            'wallet_id' => $walletId,
            'kind' => $kind,
            'minor_units' => $minorUnits,
            'currency' => $currency,
            'recorded_at' => $this->clock->now(),
        ], [
            'recorded_at' => Types::DATETIME_IMMUTABLE,
        ]);
    }
}
