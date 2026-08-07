<?php

declare(strict_types=1);

namespace Ledger\Tests\Integration;

use Doctrine\DBAL\Schema\Schema;
use Ledger\Domain\Wallet\Event\FundsDeposited;
use Ledger\Domain\Wallet\Event\FundsWithdrawn;
use Ledger\Domain\Wallet\WalletId;
use Ledger\Infrastructure\Projection\DbalTransactionHistoryReader;
use Ledger\Infrastructure\Projection\WalletTransactionProjector;
use Ledger\Infrastructure\Projection\WalletTransactionSchema;
use Ledger\Tests\Support\FrozenClock;
use Ledger\Tests\Support\ProjectionTestCase;

final class WalletTransactionProjectionTest extends ProjectionTestCase
{
    private WalletTransactionProjector $projector;

    private DbalTransactionHistoryReader $reader;

    private string $walletId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projector = new WalletTransactionProjector($this->connection, new FrozenClock());
        $this->reader = new DbalTransactionHistoryReader($this->connection);
        $this->walletId = WalletId::generate()->value;
    }

    public function testMovementsAreListedNewestFirst(): void
    {
        $this->projector->whenFundsDeposited(new FundsDeposited($this->walletId, 1000, 'SAR'));
        $this->projector->whenFundsWithdrawn(new FundsWithdrawn($this->walletId, 250, 'SAR'));

        $history = $this->reader->historyOf($this->walletId);

        self::assertCount(2, $history);
        self::assertSame(WalletTransactionProjector::WITHDRAWAL, $history[0]->kind);
        self::assertSame(250, $history[0]->minorUnits);
        self::assertSame(WalletTransactionProjector::DEPOSIT, $history[1]->kind);
    }

    public function testHistoryIsScopedToOneWallet(): void
    {
        $other = WalletId::generate()->value;

        $this->projector->whenFundsDeposited(new FundsDeposited($this->walletId, 1000, 'SAR'));
        $this->projector->whenFundsDeposited(new FundsDeposited($other, 9999, 'USD'));

        self::assertCount(1, $this->reader->historyOf($this->walletId));
        self::assertSame(9999, $this->reader->historyOf($other)[0]->minorUnits);
    }

    public function testTheLimitIsRespected(): void
    {
        for ($i = 0; $i < 5; ++$i) {
            $this->projector->whenFundsDeposited(new FundsDeposited($this->walletId, 100, 'SAR'));
        }

        self::assertCount(3, $this->reader->historyOf($this->walletId, 3));
    }

    public function testAWalletWithoutMovementsHasAnEmptyHistory(): void
    {
        self::assertSame([], $this->reader->historyOf($this->walletId));
    }

    public function testEachRowCarriesWhenItHappened(): void
    {
        $this->projector->whenFundsDeposited(new FundsDeposited($this->walletId, 100, 'SAR'));

        self::assertStringContainsString('2026-01-15', $this->reader->historyOf($this->walletId)[0]->recordedAt);
    }

    protected function configureSchema(Schema $schema): void
    {
        WalletTransactionSchema::configure($schema);
    }

    protected function tables(): array
    {
        return [WalletTransactionSchema::TABLE];
    }
}
