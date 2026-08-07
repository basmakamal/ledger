<?php

declare(strict_types=1);

namespace Ledger\Domain\Wallet;

final class WalletNotFound extends \RuntimeException
{
    public function __construct(WalletId $id)
    {
        parent::__construct(sprintf('No wallet has been opened with id %s', $id->value));
    }
}
