<?php

declare(strict_types=1);

namespace Ledger\Infrastructure\Projection;

use Doctrine\DBAL\Connection;
use Ledger\Application\Port\Projector;
use Ledger\Domain\Shared\DomainEvent;
use Ledger\Domain\Wallet\Event\FundsDeposited;
use Ledger\Domain\Wallet\Event\FundsWithdrawn;
use Ledger\Domain\Wallet\Event\WalletOpened;

/**
 * Derived state, never a source of truth. Dropping this table costs nothing
 * but the time it takes to replay the stream through it.
 */
final class WalletBalanceProjector implements Projector
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function whenWalletOpened(WalletOpened $event): void
    {
        $this->connection->insert(WalletBalanceSchema::TABLE, [
            'wallet_id' => $event->walletId,
            'currency' => $event->currency,
            'minor_units' => 0,
        ]);
    }

    public function whenFundsDeposited(FundsDeposited $event): void
    {
        $this->shift($event->walletId, $event->minorUnits);
    }

    public function whenFundsWithdrawn(FundsWithdrawn $event): void
    {
        $this->shift($event->walletId, -$event->minorUnits);
    }

    public function project(DomainEvent $event): void
    {
        match (true) {
            $event instanceof WalletOpened => $this->whenWalletOpened($event),
            $event instanceof FundsDeposited => $this->whenFundsDeposited($event),
            $event instanceof FundsWithdrawn => $this->whenFundsWithdrawn($event),
            default => null,
        };
    }

    public function reset(): void
    {
        $this->connection->executeStatement('DELETE FROM '.WalletBalanceSchema::TABLE);
    }

    private function shift(string $walletId, int $by): void
    {
        $this->connection->executeStatement(
            'UPDATE '.WalletBalanceSchema::TABLE.' SET minor_units = minor_units + ? WHERE wallet_id = ?',
            [$by, $walletId],
        );
    }
}
