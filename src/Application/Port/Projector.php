<?php

declare(strict_types=1);

namespace Ledger\Application\Port;

use Ledger\Domain\Shared\DomainEvent;

interface Projector
{
    /**
     * Throw the derived data away. Safe by definition: a projection holds
     * nothing the stream cannot produce again.
     */
    public function reset(): void;

    /**
     * Apply one event. Events the projection does not care about are ignored.
     */
    public function project(DomainEvent $event): void;
}
