<?php

declare(strict_types=1);

namespace Ledger\Application\Wallet\Command;

/**
 * The id is supplied by the caller rather than returned by the handler, so a
 * command carries no result and a retried dispatch stays idempotent.
 */
final readonly class OpenWallet
{
    public function __construct(
        public string $walletId,
        public string $currency,
    ) {
    }
}
