<?php

declare(strict_types=1);

namespace Ledger\Tests\Support;

use Ledger\Application\Port\EventPublisher;
use Ledger\Domain\Shared\DomainEvent;

final class SpyEventPublisher implements EventPublisher
{
    /** @var list<DomainEvent> */
    public array $published = [];

    public function publish(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            $this->published[] = $event;
        }
    }
}
