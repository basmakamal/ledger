<?php

declare(strict_types=1);

namespace Ledger\Domain\Wallet;

final class CurrencyMismatch extends \DomainException
{
    public function __construct(string $expected, string $given)
    {
        parent::__construct(sprintf('This wallet holds %s, not %s', $expected, $given));
    }
}
