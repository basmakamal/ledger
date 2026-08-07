<?php

declare(strict_types=1);

namespace Ledger\Infrastructure\Bus;

use Ledger\Application\Exception\ConcurrencyConflict;
use Ledger\Application\Port\CommandBus;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Losing an optimistic-locking race is not a failure, it is a signal to read
 * the stream again and reapply the decision. The retry lives here rather than
 * in middleware because a Messenger stack cannot be walked twice; a fresh
 * dispatch gives the command a clean stack and a freshly loaded aggregate.
 */
final class MessengerCommandBus implements CommandBus
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly int $attempts = 3,
    ) {
    }

    public function dispatch(object $command): void
    {
        for ($attempt = 1;; ++$attempt) {
            try {
                $this->commandBus->dispatch($command);

                return;
            } catch (HandlerFailedException $failure) {
                if ($attempt >= $this->attempts || !$this->causedByConflict($failure)) {
                    throw $failure;
                }
            }
        }
    }

    private function causedByConflict(HandlerFailedException $failure): bool
    {
        foreach ($failure->getWrappedExceptions() as $wrapped) {
            if ($wrapped instanceof ConcurrencyConflict) {
                return true;
            }
        }

        return false;
    }
}
