<?php

declare(strict_types=1);

namespace Ledger\Domain\Wallet;

use Ledger\Domain\Shared\AggregateRoot;
use Ledger\Domain\Shared\DomainEvent;
use Ledger\Domain\Wallet\Event\FundsDeposited;
use Ledger\Domain\Wallet\Event\FundsWithdrawn;
use Ledger\Domain\Wallet\Event\WalletOpened;

final class Wallet extends AggregateRoot
{
    private Money $balance;

    private function __construct(private readonly WalletId $id)
    {
    }

    public static function open(WalletId $id, string $currency): self
    {
        $wallet = new self($id);
        $wallet->recordThat(new WalletOpened($id->value, $currency));

        return $wallet;
    }

    /**
     * @param iterable<DomainEvent> $events
     */
    public static function fromStream(WalletId $id, iterable $events): self
    {
        $wallet = new self($id);
        $wallet->replay($events);

        return $wallet;
    }

    public function deposit(Money $amount): void
    {
        $this->assertAcceptable($amount);

        $this->recordThat(new FundsDeposited($this->id->value, $amount->minorUnits, $amount->currency));
    }

    public function withdraw(Money $amount): void
    {
        $this->assertAcceptable($amount);

        if ($this->balance->isLessThan($amount)) {
            throw new InsufficientFunds($this->balance, $amount);
        }

        $this->recordThat(new FundsWithdrawn($this->id->value, $amount->minorUnits, $amount->currency));
    }

    public function aggregateId(): string
    {
        return $this->id->value;
    }

    public function id(): WalletId
    {
        return $this->id;
    }

    public function balance(): Money
    {
        return $this->balance;
    }

    protected function apply(DomainEvent $event): void
    {
        match (true) {
            $event instanceof WalletOpened => $this->balance = Money::zero($event->currency),
            $event instanceof FundsDeposited => $this->balance = $this->balance->add(
                Money::of($event->minorUnits, $event->currency),
            ),
            $event instanceof FundsWithdrawn => $this->balance = $this->balance->subtract(
                Money::of($event->minorUnits, $event->currency),
            ),
            default => null,
        };
    }

    private function assertAcceptable(Money $amount): void
    {
        if (!$amount->isPositive()) {
            throw new \DomainException('An amount must be greater than zero');
        }

        if ($amount->currency !== $this->balance->currency) {
            throw new CurrencyMismatch($this->balance->currency, $amount->currency);
        }
    }
}
