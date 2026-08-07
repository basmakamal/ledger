<?php

declare(strict_types=1);

namespace Ledger\Infrastructure\Time;

use Ledger\Application\Port\Clock;

final class SystemClock implements Clock
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
