<?php

declare(strict_types=1);

namespace Ledger\Application\Port;

use Ledger\Domain\Shared\DomainEvent;

final readonly class StoredEvent
{
    public function __construct(
        public string $aggregateId,
        public int $version,
        public DomainEvent $event,
        public \DateTimeImmutable $recordedAt,
    ) {
    }
}
