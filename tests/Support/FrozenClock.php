<?php

declare(strict_types=1);

namespace Ledger\Tests\Support;

use Ledger\Application\Port\Clock;

final class FrozenClock implements Clock
{
    public function __construct(
        private \DateTimeImmutable $now = new \DateTimeImmutable('2026-01-15 10:00:00'),
    ) {
    }

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }

    public function advance(string $interval): void
    {
        $this->now = $this->now->modify($interval);
    }
}
