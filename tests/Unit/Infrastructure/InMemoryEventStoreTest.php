<?php

declare(strict_types=1);

namespace Ledger\Tests\Unit\Infrastructure;

use Ledger\Application\Port\EventStore;
use Ledger\Infrastructure\EventStore\InMemoryEventStore;
use Ledger\Tests\Support\EventStoreContractTestCase;
use Ledger\Tests\Support\FrozenClock;

final class InMemoryEventStoreTest extends EventStoreContractTestCase
{
    private InMemoryEventStore $store;

    protected function setUp(): void
    {
        $this->store = new InMemoryEventStore(new FrozenClock());
    }

    protected function store(): EventStore
    {
        return $this->store;
    }
}
