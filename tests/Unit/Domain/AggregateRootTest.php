<?php

declare(strict_types=1);

namespace Ledger\Tests\Unit\Domain;

use Ledger\Tests\Support\Fake\Counter;
use Ledger\Tests\Support\Fake\CounterIncremented;
use PHPUnit\Framework\TestCase;

final class AggregateRootTest extends TestCase
{
    public function testRecordingAnEventAppliesItAndBumpsTheVersion(): void
    {
        $counter = Counter::start('c1');

        $counter->increment(3);

        self::assertSame(3, $counter->total());
        self::assertSame(1, $counter->version());
    }

    public function testReleasingEventsEmptiesTheBuffer(): void
    {
        $counter = Counter::start('c1');
        $counter->increment(1);
        $counter->increment(2);

        $events = $counter->releaseEvents();

        self::assertCount(2, $events);
        self::assertInstanceOf(CounterIncremented::class, $events[0]);
        self::assertSame([], $counter->releaseEvents());
    }

    public function testLoadedVersionIsWhatTheStoreMustCompareAgainst(): void
    {
        $counter = Counter::fromStream('c1', [new CounterIncremented(5), new CounterIncremented(5)]);

        self::assertSame(2, $counter->loadedVersion());

        $counter->increment(1);

        self::assertSame(3, $counter->version());
        self::assertSame(2, $counter->loadedVersion(), 'appending must not move the expected version');
    }

    public function testStateIsRebuiltByFoldingTheStream(): void
    {
        $counter = Counter::fromStream('c1', [
            new CounterIncremented(10),
            new CounterIncremented(-4),
            new CounterIncremented(1),
        ]);

        self::assertSame(7, $counter->total());
        self::assertSame(3, $counter->version());
    }

    public function testReplayedEventsAreNotRecordedAgain(): void
    {
        $counter = Counter::fromStream('c1', [new CounterIncremented(1)]);

        self::assertSame([], $counter->releaseEvents());
    }
}
