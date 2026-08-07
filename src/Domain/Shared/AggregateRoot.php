<?php

declare(strict_types=1);

namespace Ledger\Domain\Shared;

abstract class AggregateRoot
{
    private int $version = 0;

    /** @var list<DomainEvent> */
    private array $recordedEvents = [];

    abstract public function aggregateId(): string;

    /**
     * Mutate state from a fact. Must stay free of validation: it runs during
     * replay, where every event has already been accepted once.
     */
    abstract protected function apply(DomainEvent $event): void;

    final public function version(): int
    {
        return $this->version;
    }

    /**
     * Version the aggregate had when it was loaded, which is what the store
     * compares against to detect a concurrent write.
     */
    final public function loadedVersion(): int
    {
        return $this->version - count($this->recordedEvents);
    }

    /**
     * @return list<DomainEvent>
     */
    final public function releaseEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }

    final protected function recordThat(DomainEvent $event): void
    {
        $this->apply($event);
        ++$this->version;
        $this->recordedEvents[] = $event;
    }

    /**
     * @param iterable<DomainEvent> $events
     */
    final protected function replay(iterable $events): void
    {
        foreach ($events as $event) {
            $this->apply($event);
            ++$this->version;
        }
    }
}
