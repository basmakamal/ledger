<?php

declare(strict_types=1);

namespace Ledger\Application\Wallet\Handler;

use Ledger\Application\Wallet\Command\WithdrawFunds;
use Ledger\Domain\Wallet\Money;
use Ledger\Domain\Wallet\WalletId;
use Ledger\Domain\Wallet\WalletRepository;

final class WithdrawFundsHandler
{
    public function __construct(private readonly WalletRepository $wallets)
    {
    }

    public function __invoke(WithdrawFunds $command): void
    {
        $wallet = $this->wallets->get(WalletId::fromString($command->walletId));

        $wallet->withdraw(Money::of($command->minorUnits, $command->currency));

        $this->wallets->save($wallet);
    }
}
