<?php

declare(strict_types=1);

namespace Ledger\Domain\Wallet\Event;

use Ledger\Domain\Shared\DomainEvent;

final readonly class WalletOpened implements DomainEvent
{
    public function __construct(
        public string $walletId,
        public string $currency,
    ) {
    }

    public static function eventType(): string
    {
        return 'wallet.opened';
    }

    public function toPayload(): array
    {
        return [
            'wallet_id' => $this->walletId,
            'currency' => $this->currency,
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self((string) $payload['wallet_id'], (string) $payload['currency']);
    }
}
