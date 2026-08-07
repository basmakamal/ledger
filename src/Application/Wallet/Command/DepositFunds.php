<?php

declare(strict_types=1);

namespace Ledger\Application\Wallet\Command;

final readonly class DepositFunds
{
    public function __construct(
        public string $walletId,
        public int $minorUnits,
        public string $currency,
    ) {
    }
}
