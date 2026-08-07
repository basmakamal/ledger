<?php

declare(strict_types=1);

namespace Ledger\Application\Wallet\Query;

interface TransactionHistoryReader
{
    /**
     * @return list<WalletTransaction> newest first
     */
    public function historyOf(string $walletId, int $limit = 50): array;
}
