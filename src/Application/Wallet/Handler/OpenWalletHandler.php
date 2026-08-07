<?php

declare(strict_types=1);

namespace Ledger\Application\Wallet\Handler;

use Ledger\Application\Wallet\Command\OpenWallet;
use Ledger\Domain\Wallet\Wallet;
use Ledger\Domain\Wallet\WalletId;
use Ledger\Domain\Wallet\WalletRepository;

final class OpenWalletHandler
{
    public function __construct(private readonly WalletRepository $wallets)
    {
    }

    public function __invoke(OpenWallet $command): void
    {
        $this->wallets->save(
            Wallet::open(WalletId::fromString($command->walletId), $command->currency),
        );
    }
}
