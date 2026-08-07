<?php

declare(strict_types=1);

namespace Ledger\Application\Projection;

use Ledger\Application\Port\EventStore;
use Ledger\Application\Port\Projector;
use Ledger\Application\Port\StoredEvent;

/**
 * Read models are disposable. This walks the whole stream and rebuilds them,
 * which is the answer to a projection bug, a new report, or a schema change:
 * fix the projector, replay, done.
 */
final class RebuildProjections
{
    /**
     * @param iterable<Projector> $projectors
     */
    public function __construct(
        private readonly EventStore $store,
        private readonly iterable $projectors,
    ) {
    }

    /**
     * @return int number of events replayed
     */
    public function __invoke(): int
    {
        foreach ($this->projectors as $projector) {
            $projector->reset();
        }

        $replayed = 0;

        /** @var StoredEvent $stored */
        foreach ($this->store->readAll() as $stored) {
            foreach ($this->projectors as $projector) {
                $projector->project($stored->event);
            }

            ++$replayed;
        }

        return $replayed;
    }
}
