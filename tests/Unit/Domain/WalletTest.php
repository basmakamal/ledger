<?php

declare(strict_types=1);

namespace Ledger\Tests\Unit\Domain;

use Ledger\Domain\Wallet\CurrencyMismatch;
use Ledger\Domain\Wallet\Event\FundsDeposited;
use Ledger\Domain\Wallet\Event\WalletOpened;
use Ledger\Domain\Wallet\Money;
use Ledger\Domain\Wallet\Wallet;
use Ledger\Domain\Wallet\WalletId;
use PHPUnit\Framework\TestCase;

final class WalletTest extends TestCase
{
    public function testOpeningRecordsTheFactAndStartsEmpty(): void
    {
        $id = WalletId::generate();

        $wallet = Wallet::open($id, 'SAR');

        self::assertTrue($wallet->balance()->equals(Money::zero('SAR')));
        self::assertSame(1, $wallet->version());

        $events = $wallet->releaseEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(WalletOpened::class, $events[0]);
        self::assertSame($id->value, $events[0]->walletId);
    }

    public function testDepositsAccumulate(): void
    {
        $wallet = Wallet::open(WalletId::generate(), 'SAR');

        $wallet->deposit(Money::of(2500, 'SAR'));
        $wallet->deposit(Money::of(1000, 'SAR'));

        self::assertSame(3500, $wallet->balance()->minorUnits);
        self::assertSame(3, $wallet->version());
    }

    public function testADepositRecordsTheAmountItAccepted(): void
    {
        $wallet = Wallet::open(WalletId::generate(), 'SAR');
        $wallet->releaseEvents();

        $wallet->deposit(Money::of(2500, 'SAR'));

        $events = $wallet->releaseEvents();

        self::assertInstanceOf(FundsDeposited::class, $events[0]);
        self::assertSame(2500, $events[0]->minorUnits);
        self::assertSame('SAR', $events[0]->currency);
    }

    public function testDepositsMustBePositive(): void
    {
        $wallet = Wallet::open(WalletId::generate(), 'SAR');

        $this->expectException(\DomainException::class);

        $wallet->deposit(Money::zero('SAR'));
    }

    public function testDepositsMustMatchTheWalletCurrency(): void
    {
        $wallet = Wallet::open(WalletId::generate(), 'SAR');

        $this->expectException(CurrencyMismatch::class);

        $wallet->deposit(Money::of(100, 'USD'));
    }

    public function testARejectedDepositLeavesNoEventBehind(): void
    {
        $wallet = Wallet::open(WalletId::generate(), 'SAR');
        $wallet->releaseEvents();

        try {
            $wallet->deposit(Money::of(-1, 'SAR'));
        } catch (\DomainException) {
            // expected
        }

        self::assertSame([], $wallet->releaseEvents());
        self::assertSame(1, $wallet->version());
    }
}
