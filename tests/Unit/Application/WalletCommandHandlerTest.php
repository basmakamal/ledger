<?php

declare(strict_types=1);

namespace Ledger\Tests\Unit\Application;

use Ledger\Application\Wallet\Command\DepositFunds;
use Ledger\Application\Wallet\Command\OpenWallet;
use Ledger\Application\Wallet\Command\WithdrawFunds;
use Ledger\Application\Wallet\Handler\DepositFundsHandler;
use Ledger\Application\Wallet\Handler\OpenWalletHandler;
use Ledger\Application\Wallet\Handler\WithdrawFundsHandler;
use Ledger\Domain\Wallet\InsufficientFunds;
use Ledger\Domain\Wallet\WalletId;
use Ledger\Domain\Wallet\WalletNotFound;
use Ledger\Domain\Wallet\WalletRepository;
use Ledger\Infrastructure\EventStore\InMemoryEventStore;
use Ledger\Infrastructure\Repository\EventSourcedWalletRepository;
use Ledger\Tests\Support\FrozenClock;
use PHPUnit\Framework\TestCase;

final class WalletCommandHandlerTest extends TestCase
{
    private WalletRepository $wallets;

    private WalletId $id;

    protected function setUp(): void
    {
        $this->wallets = new EventSourcedWalletRepository(new InMemoryEventStore(new FrozenClock()));
        $this->id = WalletId::generate();
    }

    public function testOpeningPersistsAnEmptyWallet(): void
    {
        $this->open();

        self::assertSame(0, $this->wallets->get($this->id)->balance()->minorUnits);
    }

    public function testDepositsAndWithdrawalsMoveTheBalance(): void
    {
        $this->open();

        (new DepositFundsHandler($this->wallets))(new DepositFunds($this->id->value, 9000, 'SAR'));
        (new WithdrawFundsHandler($this->wallets))(new WithdrawFunds($this->id->value, 4000, 'SAR'));

        self::assertSame(5000, $this->wallets->get($this->id)->balance()->minorUnits);
    }

    public function testDepositingIntoAnUnopenedWalletFails(): void
    {
        $this->expectException(WalletNotFound::class);

        (new DepositFundsHandler($this->wallets))(new DepositFunds($this->id->value, 100, 'SAR'));
    }

    public function testDomainRulesStillApplyThroughTheHandler(): void
    {
        $this->open();

        $this->expectException(InsufficientFunds::class);

        (new WithdrawFundsHandler($this->wallets))(new WithdrawFunds($this->id->value, 1, 'SAR'));
    }

    private function open(): void
    {
        (new OpenWalletHandler($this->wallets))(new OpenWallet($this->id->value, 'SAR'));
    }
}
