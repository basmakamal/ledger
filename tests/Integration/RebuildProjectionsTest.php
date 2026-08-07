<?php

declare(strict_types=1);

namespace Ledger\Tests\Integration;

use Doctrine\DBAL\Schema\Schema;
use Ledger\Application\Projection\RebuildProjections;
use Ledger\Domain\Wallet\Event\FundsDeposited;
use Ledger\Domain\Wallet\Event\FundsWithdrawn;
use Ledger\Domain\Wallet\Event\WalletOpened;
use Ledger\Domain\Wallet\WalletId;
use Ledger\Infrastructure\EventStore\InMemoryEventStore;
use Ledger\Infrastructure\Projection\DbalBalanceReader;
use Ledger\Infrastructure\Projection\DbalTransactionHistoryReader;
use Ledger\Infrastructure\Projection\WalletBalanceProjector;
use Ledger\Infrastructure\Projection\WalletBalanceSchema;
use Ledger\Infrastructure\Projection\WalletTransactionProjector;
use Ledger\Infrastructure\Projection\WalletTransactionSchema;
use Ledger\Tests\Support\FrozenClock;
use Ledger\Tests\Support\ProjectionTestCase;

final class RebuildProjectionsTest extends ProjectionTestCase
{
    private InMemoryEventStore $store;

    private RebuildProjections $rebuild;

    private DbalBalanceReader $balances;

    private DbalTransactionHistoryReader $history;

    private string $walletId;

    protected function setUp(): void
    {
        parent::setUp();

        $clock = new FrozenClock();

        $this->store = new InMemoryEventStore($clock);
        $this->balances = new DbalBalanceReader($this->connection);
        $this->history = new DbalTransactionHistoryReader($this->connection);
        $this->rebuild = new RebuildProjections($this->store, [
            new WalletBalanceProjector($this->connection),
            new WalletTransactionProjector($this->connection, $clock),
        ]);

        $this->walletId = WalletId::generate()->value;
    }

    public function testProjectionsAreBuiltFromAnEmptyDatabase(): void
    {
        $this->givenAStream();

        $replayed = ($this->rebuild)();

        self::assertSame(4, $replayed);
        self::assertSame(7500, $this->balances->balanceOf($this->walletId)?->minorUnits);
        self::assertCount(3, $this->history->historyOf($this->walletId));
    }

    public function testRebuildingTwiceIsIdempotent(): void
    {
        $this->givenAStream();

        ($this->rebuild)();
        ($this->rebuild)();

        self::assertSame(7500, $this->balances->balanceOf($this->walletId)?->minorUnits);
        self::assertCount(3, $this->history->historyOf($this->walletId));
    }

    public function testCorruptedReadModelsAreRepairedByAReplay(): void
    {
        $this->givenAStream();
        ($this->rebuild)();

        $this->connection->executeStatement(
            'UPDATE '.WalletBalanceSchema::TABLE.' SET minor_units = 999999',
        );
        $this->connection->executeStatement('DELETE FROM '.WalletTransactionSchema::TABLE);

        ($this->rebuild)();

        self::assertSame(7500, $this->balances->balanceOf($this->walletId)?->minorUnits);
        self::assertCount(3, $this->history->historyOf($this->walletId));
    }

    public function testAnEmptyStreamLeavesEmptyProjections(): void
    {
        self::assertSame(0, ($this->rebuild)());
        self::assertSame([], $this->balances->all());
    }

    private function givenAStream(): void
    {
        $this->store->append($this->walletId, 0, [
            new WalletOpened($this->walletId, 'SAR'),
            new FundsDeposited($this->walletId, 10000, 'SAR'),
            new FundsWithdrawn($this->walletId, 3000, 'SAR'),
            new FundsDeposited($this->walletId, 500, 'SAR'),
        ]);
    }

    protected function configureSchema(Schema $schema): void
    {
        WalletBalanceSchema::configure($schema);
        WalletTransactionSchema::configure($schema);
    }

    protected function tables(): array
    {
        return [WalletBalanceSchema::TABLE, WalletTransactionSchema::TABLE];
    }
}
