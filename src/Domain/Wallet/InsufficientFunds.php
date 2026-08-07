<?php

declare(strict_types=1);

namespace Ledger\Domain\Wallet;

final class InsufficientFunds extends \DomainException
{
    public function __construct(Money $balance, Money $requested)
    {
        parent::__construct(sprintf(
            'Cannot withdraw %d %s from a balance of %d',
            $requested->minorUnits,
            $requested->currency,
            $balance->minorUnits,
        ));
    }
}
