<?php

declare(strict_types=1);

namespace Ledger\Application\Wallet\Handler;

use Ledger\Application\Wallet\Command\DepositFunds;
use Ledger\Domain\Wallet\Money;
use Ledger\Domain\Wallet\WalletId;
use Ledger\Domain\Wallet\WalletRepository;

final class DepositFundsHandler
{
    public function __construct(private readonly WalletRepository $wallets)
    {
    }

    public function __invoke(DepositFunds $command): void
    {
        $wallet = $this->wallets->get(WalletId::fromString($command->walletId));

        $wallet->deposit(Money::of($command->minorUnits, $command->currency));

        $this->wallets->save($wallet);
    }
}
