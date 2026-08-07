<?php

declare(strict_types=1);

namespace Ledger\Application\Port;

/**
 * Own port rather than PSR-20 so the application layer keeps depending on
 * nothing but itself and the domain.
 */
interface Clock
{
    public function now(): \DateTimeImmutable;
}
