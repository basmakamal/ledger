<?php

declare(strict_types=1);

namespace Ledger\Application\Wallet\Query;

interface BalanceReader
{
    /**
     * Reads the projection, not the stream: answering "what is the balance"
     * must not cost a replay.
     */
    public function balanceOf(string $walletId): ?WalletBalance;

    /**
     * @return list<WalletBalance>
     */
    public function all(): array;
}
