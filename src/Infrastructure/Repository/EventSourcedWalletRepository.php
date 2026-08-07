<?php

declare(strict_types=1);

namespace Ledger\Infrastructure\Repository;

use Ledger\Application\Port\EventStore;
use Ledger\Application\Port\StoredEvent;
use Ledger\Domain\Shared\DomainEvent;
use Ledger\Domain\Wallet\Wallet;
use Ledger\Domain\Wallet\WalletId;
use Ledger\Domain\Wallet\WalletNotFound;
use Ledger\Domain\Wallet\WalletRepository;

final class EventSourcedWalletRepository implements WalletRepository
{
    public function __construct(private readonly EventStore $store)
    {
    }

    public function get(WalletId $id): Wallet
    {
        $stream = $this->store->load($id->value);

        if ([] === $stream) {
            throw new WalletNotFound($id);
        }

        return Wallet::fromStream($id, array_map(
            static fn (StoredEvent $stored): DomainEvent => $stored->event,
            $stream,
        ));
    }

    public function save(Wallet $wallet): void
    {
        // Read before releasing: the expected version is derived from how many
        // events are still pending, so releasing first would always report the
        // stream as untouched and defeat the concurrency check.
        $expectedVersion = $wallet->loadedVersion();

        $events = $wallet->releaseEvents();

        if ([] === $events) {
            return;
        }

        $this->store->append($wallet->aggregateId(), $expectedVersion, $events);
    }
}
