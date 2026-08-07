<?php

declare(strict_types=1);

namespace Ledger\Tests\Unit\Domain;

use Ledger\Domain\Wallet\CurrencyMismatch;
use Ledger\Domain\Wallet\Event\FundsWithdrawn;
use Ledger\Domain\Wallet\InsufficientFunds;
use Ledger\Domain\Wallet\Money;
use Ledger\Domain\Wallet\Wallet;
use Ledger\Domain\Wallet\WalletId;
use PHPUnit\Framework\TestCase;

final class WalletWithdrawalTest extends TestCase
{
    public function testWithdrawalReducesTheBalance(): void
    {
        $wallet = $this->fundedWallet(5000);

        $wallet->withdraw(Money::of(1500, 'SAR'));

        self::assertSame(3500, $wallet->balance()->minorUnits);
    }

    public function testTheWholeBalanceCanBeWithdrawn(): void
    {
        $wallet = $this->fundedWallet(5000);

        $wallet->withdraw(Money::of(5000, 'SAR'));

        self::assertSame(0, $wallet->balance()->minorUnits);
    }

    public function testWithdrawingOneUnitTooMuchIsRejected(): void
    {
        $wallet = $this->fundedWallet(5000);

        $this->expectException(InsufficientFunds::class);

        $wallet->withdraw(Money::of(5001, 'SAR'));
    }

    public function testAnEmptyWalletCannotBeDrawnOn(): void
    {
        $wallet = Wallet::open(WalletId::generate(), 'SAR');

        $this->expectException(InsufficientFunds::class);

        $wallet->withdraw(Money::of(1, 'SAR'));
    }

    public function testARejectedWithdrawalRecordsNothing(): void
    {
        $wallet = $this->fundedWallet(100);
        $wallet->releaseEvents();
        $versionBefore = $wallet->version();

        try {
            $wallet->withdraw(Money::of(500, 'SAR'));
        } catch (InsufficientFunds) {
            // expected
        }

        self::assertSame([], $wallet->releaseEvents());
        self::assertSame($versionBefore, $wallet->version());
        self::assertSame(100, $wallet->balance()->minorUnits);
    }

    public function testWithdrawalsMustBePositive(): void
    {
        $wallet = $this->fundedWallet(100);

        $this->expectException(\DomainException::class);

        $wallet->withdraw(Money::of(-50, 'SAR'));
    }

    public function testWithdrawalsMustMatchTheWalletCurrency(): void
    {
        $wallet = $this->fundedWallet(100);

        $this->expectException(CurrencyMismatch::class);

        $wallet->withdraw(Money::of(50, 'USD'));
    }

    public function testTheWithdrawalIsRecordedAsAFact(): void
    {
        $wallet = $this->fundedWallet(1000);
        $wallet->releaseEvents();

        $wallet->withdraw(Money::of(250, 'SAR'));

        $events = $wallet->releaseEvents();

        self::assertInstanceOf(FundsWithdrawn::class, $events[0]);
        self::assertSame(250, $events[0]->minorUnits);
    }

    private function fundedWallet(int $minorUnits): Wallet
    {
        $wallet = Wallet::open(WalletId::generate(), 'SAR');
        $wallet->deposit(Money::of($minorUnits, 'SAR'));

        return $wallet;
    }
}
