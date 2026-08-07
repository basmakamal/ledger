<?php

declare(strict_types=1);

namespace Ledger\Tests\Support\Fake;

use Ledger\Domain\Shared\DomainEvent;

final readonly class CounterIncremented implements DomainEvent
{
    public function __construct(public int $by)
    {
    }

    public static function eventType(): string
    {
        return 'test.counter_incremented';
    }

    public function toPayload(): array
    {
        return ['by' => $this->by];
    }

    public static function fromPayload(array $payload): self
    {
        return new self((int) $payload['by']);
    }
}
