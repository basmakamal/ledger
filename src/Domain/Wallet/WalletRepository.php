<?php

declare(strict_types=1);

namespace Ledger\Domain\Wallet;

interface WalletRepository
{
    /**
     * @throws WalletNotFound
     */
    public function get(WalletId $id): Wallet;

    public function save(Wallet $wallet): void;
}
