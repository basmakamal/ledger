<?php

declare(strict_types=1);

namespace Ledger\Tests\Support\Fake;

use Ledger\Domain\Shared\AggregateRoot;
use Ledger\Domain\Shared\DomainEvent;

/**
 * Minimal aggregate used to exercise the base class without dragging the
 * wallet's business rules into these tests.
 */
final class Counter extends AggregateRoot
{
    private int $total = 0;

    private function __construct(private readonly string $id)
    {
    }

    public static function start(string $id): self
    {
        return new self($id);
    }

    /**
     * @param iterable<DomainEvent> $events
     */
    public static function fromStream(string $id, iterable $events): self
    {
        $counter = new self($id);
        $counter->replay($events);

        return $counter;
    }

    public function increment(int $by): void
    {
        $this->recordThat(new CounterIncremented($by));
    }

    public function aggregateId(): string
    {
        return $this->id;
    }

    public function total(): int
    {
        return $this->total;
    }

    protected function apply(DomainEvent $event): void
    {
        if ($event instanceof CounterIncremented) {
            $this->total += $event->by;
        }
    }
}
