<?php

declare(strict_types=1);

namespace Ledger\Tests\Unit\Infrastructure;

use Ledger\Application\Exception\ConcurrencyConflict;
use Ledger\Infrastructure\EventStore\InMemoryEventStore;
use Ledger\Infrastructure\EventStore\PublishingEventStore;
use Ledger\Tests\Support\Fake\CounterIncremented;
use Ledger\Tests\Support\FrozenClock;
use Ledger\Tests\Support\SpyEventPublisher;
use PHPUnit\Framework\TestCase;

final class PublishingEventStoreTest extends TestCase
{
    private SpyEventPublisher $publisher;

    private PublishingEventStore $store;

    protected function setUp(): void
    {
        $this->publisher = new SpyEventPublisher();
        $this->store = new PublishingEventStore(
            new InMemoryEventStore(new FrozenClock()),
            $this->publisher,
        );
    }

    public function testAppendedEventsAreAnnounced(): void
    {
        $this->store->append('c1', 0, [new CounterIncremented(1), new CounterIncremented(2)]);

        self::assertCount(2, $this->publisher->published);
    }

    public function testEventsStillReachTheUnderlyingStream(): void
    {
        $this->store->append('c1', 0, [new CounterIncremented(1)]);

        self::assertCount(1, $this->store->load('c1'));
    }

    public function testARejectedAppendAnnouncesNothing(): void
    {
        $this->store->append('c1', 0, [new CounterIncremented(1)]);
        $this->publisher->published = [];

        try {
            $this->store->append('c1', 0, [new CounterIncremented(2)]);
        } catch (ConcurrencyConflict) {
            // expected
        }

        self::assertSame([], $this->publisher->published, 'a conflict must not leak phantom events');
    }
}
