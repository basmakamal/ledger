<?php

declare(strict_types=1);

namespace Ledger\Tests\Support;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Fails a configured number of times before succeeding, so retry behaviour
 * can be observed without racing two real writers.
 */
final class ScriptedMessageBus implements MessageBusInterface
{
    public int $dispatches = 0;

    /**
     * @param list<\Throwable> $failures thrown in order, one per dispatch
     */
    public function __construct(private array $failures = [])
    {
    }

    public function dispatch(object $message, array $stamps = []): Envelope
    {
        ++$this->dispatches;

        $failure = array_shift($this->failures);

        if (null !== $failure) {
            throw $failure;
        }

        return new Envelope($message);
    }
}
