<?php

declare(strict_types=1);

namespace Ledger\Application\Exception;

final class ConcurrencyConflict extends \RuntimeException
{
    public static function for(string $aggregateId, int $expectedVersion): self
    {
        return new self(sprintf(
            'Stream "%s" is no longer at version %d; another writer got there first',
            $aggregateId,
            $expectedVersion,
        ));
    }
}
