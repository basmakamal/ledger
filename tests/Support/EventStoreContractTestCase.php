<?php

declare(strict_types=1);

namespace Ledger\Tests\Support;

use Ledger\Application\Exception\ConcurrencyConflict;
use Ledger\Application\Port\EventStore;
use Ledger\Tests\Support\Fake\CounterIncremented;
use PHPUnit\Framework\TestCase;

/**
 * Every event store implementation runs this suite, so the in-memory store
 * used in unit tests cannot quietly drift from the one used in production.
 */
abstract class EventStoreContractTestCase extends TestCase
{
    abstract protected function store(): EventStore;

    public function testLoadingAnUnknownStreamReturnsNothing(): void
    {
        self::assertSame([], $this->store()->load('does-not-exist'));
    }

    public function testAppendedEventsComeBackInOrder(): void
    {
        $store = $this->store();

        $store->append('c1', 0, [new CounterIncremented(1), new CounterIncremented(2)]);

        $stream = $store->load('c1');

        self::assertCount(2, $stream);

        foreach ([0 => 1, 1 => 2] as $index => $expected) {
            $event = $stream[$index]->event;

            self::assertInstanceOf(CounterIncremented::class, $event);
            self::assertSame($expected, $event->by);
        }
    }

    public function testVersionsAreContiguousAndStartAtOne(): void
    {
        $store = $this->store();

        $store->append('c1', 0, [new CounterIncremented(1)]);
        $store->append('c1', 1, [new CounterIncremented(1), new CounterIncremented(1)]);

        self::assertSame([1, 2, 3], array_map(
            static fn ($stored): int => $stored->version,
            $store->load('c1'),
        ));
    }

    public function testAppendingWithAStaleVersionIsRejected(): void
    {
        $store = $this->store();
        $store->append('c1', 0, [new CounterIncremented(1)]);

        $this->expectException(ConcurrencyConflict::class);

        $store->append('c1', 0, [new CounterIncremented(9)]);
    }

    public function testTheLosingWriterLeavesNoTrace(): void
    {
        $store = $this->store();
        $store->append('c1', 0, [new CounterIncremented(1)]);

        try {
            $store->append('c1', 0, [new CounterIncremented(9), new CounterIncremented(9)]);
        } catch (ConcurrencyConflict) {
            // expected
        }

        self::assertCount(1, $store->load('c1'), 'a rejected append must not write partial events');
    }

    public function testStreamsAreIsolatedFromEachOther(): void
    {
        $store = $this->store();

        $store->append('c1', 0, [new CounterIncremented(1)]);
        $store->append('c2', 0, [new CounterIncremented(2), new CounterIncremented(3)]);

        self::assertCount(1, $store->load('c1'));
        self::assertCount(2, $store->load('c2'));
    }

    public function testAppendingNothingIsHarmless(): void
    {
        $store = $this->store();

        $store->append('c1', 0, []);

        self::assertSame([], $store->load('c1'));
    }

    public function testEventsCarryTheirRecordingTime(): void
    {
        $store = $this->store();

        $store->append('c1', 0, [new CounterIncremented(1)]);

        self::assertInstanceOf(\DateTimeImmutable::class, $store->load('c1')[0]->recordedAt);
    }
}
