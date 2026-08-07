<?php

declare(strict_types=1);

namespace Ledger\Application\Wallet\Query;

final readonly class WalletBalance
{
    public function __construct(
        public string $walletId,
        public int $minorUnits,
        public string $currency,
    ) {
    }
}
