<?php

declare(strict_types=1);

namespace Ledger\Application\Port;

use Ledger\Domain\Shared\DomainEvent;

interface EventPublisher
{
    public function publish(DomainEvent ...$events): void;
}
