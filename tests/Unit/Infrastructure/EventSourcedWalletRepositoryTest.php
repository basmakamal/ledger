<?php

declare(strict_types=1);

namespace Ledger\Tests\Unit\Infrastructure;

use Ledger\Application\Exception\ConcurrencyConflict;
use Ledger\Domain\Wallet\Money;
use Ledger\Domain\Wallet\Wallet;
use Ledger\Domain\Wallet\WalletId;
use Ledger\Domain\Wallet\WalletNotFound;
use Ledger\Infrastructure\EventStore\InMemoryEventStore;
use Ledger\Infrastructure\Repository\EventSourcedWalletRepository;
use Ledger\Tests\Support\FrozenClock;
use PHPUnit\Framework\TestCase;

final class EventSourcedWalletRepositoryTest extends TestCase
{
    private EventSourcedWalletRepository $wallets;

    protected function setUp(): void
    {
        $this->wallets = new EventSourcedWalletRepository(new InMemoryEventStore(new FrozenClock()));
    }

    public function testAWalletSurvivesTheRoundTrip(): void
    {
        $id = WalletId::generate();
        $wallet = Wallet::open($id, 'SAR');
        $wallet->deposit(Money::of(7500, 'SAR'));
        $wallet->withdraw(Money::of(500, 'SAR'));

        $this->wallets->save($wallet);

        $loaded = $this->wallets->get($id);

        self::assertSame(7000, $loaded->balance()->minorUnits);
        self::assertSame(3, $loaded->version());
    }

    public function testAnUnopenedWalletIsNotFound(): void
    {
        $this->expectException(WalletNotFound::class);

        $this->wallets->get(WalletId::generate());
    }

    public function testFurtherChangesAppendToTheSameStream(): void
    {
        $id = WalletId::generate();
        $this->wallets->save(Wallet::open($id, 'SAR'));

        $wallet = $this->wallets->get($id);
        $wallet->deposit(Money::of(1000, 'SAR'));
        $this->wallets->save($wallet);

        self::assertSame(1000, $this->wallets->get($id)->balance()->minorUnits);
        self::assertSame(2, $this->wallets->get($id)->version());
    }

    public function testTwoWritersFromTheSameVersionCannotBothWin(): void
    {
        $id = WalletId::generate();
        $this->wallets->save(Wallet::open($id, 'SAR'));

        $first = $this->wallets->get($id);
        $second = $this->wallets->get($id);

        $first->deposit(Money::of(100, 'SAR'));
        $second->deposit(Money::of(999, 'SAR'));

        $this->wallets->save($first);

        $this->expectException(ConcurrencyConflict::class);

        $this->wallets->save($second);
    }

    public function testSavingAWalletWithNothingPendingIsANoop(): void
    {
        $id = WalletId::generate();
        $this->wallets->save(Wallet::open($id, 'SAR'));

        $wallet = $this->wallets->get($id);

        $this->wallets->save($wallet);
        $this->wallets->save($wallet);

        self::assertSame(1, $this->wallets->get($id)->version());
    }
}
