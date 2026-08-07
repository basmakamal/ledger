<?php

declare(strict_types=1);

namespace Ledger\Tests\Integration;

use Doctrine\DBAL\Schema\Schema;
use Ledger\Domain\Wallet\Event\FundsDeposited;
use Ledger\Domain\Wallet\Event\FundsWithdrawn;
use Ledger\Domain\Wallet\Event\WalletOpened;
use Ledger\Domain\Wallet\WalletId;
use Ledger\Infrastructure\Projection\DbalBalanceReader;
use Ledger\Infrastructure\Projection\WalletBalanceProjector;
use Ledger\Infrastructure\Projection\WalletBalanceSchema;
use Ledger\Tests\Support\ProjectionTestCase;

final class WalletBalanceProjectionTest extends ProjectionTestCase
{
    private WalletBalanceProjector $projector;

    private DbalBalanceReader $reader;

    private string $walletId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projector = new WalletBalanceProjector($this->connection);
        $this->reader = new DbalBalanceReader($this->connection);
        $this->walletId = WalletId::generate()->value;
    }

    public function testAnOpenedWalletStartsAtZero(): void
    {
        $this->projector->whenWalletOpened(new WalletOpened($this->walletId, 'SAR'));

        $balance = $this->reader->balanceOf($this->walletId);

        self::assertNotNull($balance);
        self::assertSame(0, $balance->minorUnits);
        self::assertSame('SAR', $balance->currency);
    }

    public function testTheProjectionFollowsTheEvents(): void
    {
        $this->projector->whenWalletOpened(new WalletOpened($this->walletId, 'SAR'));
        $this->projector->whenFundsDeposited(new FundsDeposited($this->walletId, 12000, 'SAR'));
        $this->projector->whenFundsWithdrawn(new FundsWithdrawn($this->walletId, 4500, 'SAR'));
        $this->projector->whenFundsDeposited(new FundsDeposited($this->walletId, 500, 'SAR'));

        $balance = $this->reader->balanceOf($this->walletId);

        self::assertNotNull($balance);
        self::assertSame(8000, $balance->minorUnits);
    }

    public function testAnUnknownWalletHasNoRow(): void
    {
        self::assertNull($this->reader->balanceOf(WalletId::generate()->value));
    }

    public function testWalletsAreProjectedIndependently(): void
    {
        $other = WalletId::generate()->value;

        $this->projector->whenWalletOpened(new WalletOpened($this->walletId, 'SAR'));
        $this->projector->whenWalletOpened(new WalletOpened($other, 'USD'));
        $this->projector->whenFundsDeposited(new FundsDeposited($this->walletId, 100, 'SAR'));

        self::assertSame(100, $this->reader->balanceOf($this->walletId)?->minorUnits);
        self::assertSame(0, $this->reader->balanceOf($other)?->minorUnits);
        self::assertCount(2, $this->reader->all());
    }

    public function testResettingClearsTheProjection(): void
    {
        $this->projector->whenWalletOpened(new WalletOpened($this->walletId, 'SAR'));
        $this->projector->whenFundsDeposited(new FundsDeposited($this->walletId, 100, 'SAR'));

        $this->projector->reset();

        self::assertSame([], $this->reader->all());
    }

    protected function configureSchema(Schema $schema): void
    {
        WalletBalanceSchema::configure($schema);
    }

    protected function tables(): array
    {
        return [WalletBalanceSchema::TABLE];
    }
}
