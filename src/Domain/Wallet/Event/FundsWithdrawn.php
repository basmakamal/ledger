<?php

declare(strict_types=1);

namespace Ledger\Domain\Wallet\Event;

use Ledger\Domain\Shared\DomainEvent;

final readonly class FundsWithdrawn implements DomainEvent
{
    public function __construct(
        public string $walletId,
        public int $minorUnits,
        public string $currency,
    ) {
    }

    public static function eventType(): string
    {
        return 'wallet.funds_withdrawn';
    }

    public function toPayload(): array
    {
        return [
            'wallet_id' => $this->walletId,
            'minor_units' => $this->minorUnits,
            'currency' => $this->currency,
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            (string) $payload['wallet_id'],
            (int) $payload['minor_units'],
            (string) $payload['currency'],
        );
    }
}
