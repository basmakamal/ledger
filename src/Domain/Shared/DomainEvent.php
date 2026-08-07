<?php

declare(strict_types=1);

namespace Ledger\Domain\Shared;

/**
 * Events carry business facts only. Metadata that belongs to the stream
 * rather than the fact — version, storage timestamp — lives in the envelope
 * the event store wraps around them.
 */
interface DomainEvent
{
    /**
     * Stable name used in storage. It is deliberately not the class name:
     * replaying a five-year-old stream must survive a namespace refactor.
     */
    public static function eventType(): string;

    /**
     * @return array<string, scalar|null>
     */
    public function toPayload(): array;

    /**
     * @param array<string, scalar|null> $payload
     */
    public static function fromPayload(array $payload): self;
}
