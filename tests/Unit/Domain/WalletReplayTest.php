<?php

declare(strict_types=1);

namespace Ledger\Tests\Unit\Domain;

use Ledger\Domain\Wallet\Event\FundsDeposited;
use Ledger\Domain\Wallet\Event\FundsWithdrawn;
use Ledger\Domain\Wallet\Event\WalletOpened;
use Ledger\Domain\Wallet\Money;
use Ledger\Domain\Wallet\Wallet;
use Ledger\Domain\Wallet\WalletId;
use PHPUnit\Framework\TestCase;

final class WalletReplayTest extends TestCase
{
    public function testTheBalanceIsAFoldOverTheStream(): void
    {
        $id = WalletId::generate();

        $wallet = Wallet::fromStream($id, [
            new WalletOpened($id->value, 'SAR'),
            new FundsDeposited($id->value, 10000, 'SAR'),
            new FundsWithdrawn($id->value, 2500, 'SAR'),
            new FundsDeposited($id->value, 500, 'SAR'),
        ]);

        self::assertSame(8000, $wallet->balance()->minorUnits);
        self::assertSame(4, $wallet->version());
    }

    public function testReplayingProducesNoNewEvents(): void
    {
        $id = WalletId::generate();

        $wallet = Wallet::fromStream($id, [
            new WalletOpened($id->value, 'SAR'),
            new FundsDeposited($id->value, 100, 'SAR'),
        ]);

        self::assertSame([], $wallet->releaseEvents());
        self::assertSame(2, $wallet->loadedVersion());
    }

    public function testABusinessRuleIsCheckedAgainstTheReplayedState(): void
    {
        $id = WalletId::generate();
        $wallet = Wallet::fromStream($id, [
            new WalletOpened($id->value, 'SAR'),
            new FundsDeposited($id->value, 300, 'SAR'),
        ]);

        $wallet->withdraw(Money::of(300, 'SAR'));

        self::assertSame(0, $wallet->balance()->minorUnits);
        self::assertSame(3, $wallet->version());
        self::assertSame(2, $wallet->loadedVersion(), 'the expected version must still point at the loaded stream');
    }

    public function testReplayIgnoresEventsTheAggregateDoesNotKnow(): void
    {
        $id = WalletId::generate();

        $wallet = Wallet::fromStream($id, [
            new WalletOpened($id->value, 'SAR'),
            new \Ledger\Tests\Support\Fake\CounterIncremented(9),
        ]);

        self::assertSame(0, $wallet->balance()->minorUnits);
    }
}
