<?php

declare(strict_types=1);

namespace Ledger\Application\Wallet\Query;

final readonly class WalletTransaction
{
    public function __construct(
        public string $kind,
        public int $minorUnits,
        public string $currency,
        public string $recordedAt,
    ) {
    }
}
