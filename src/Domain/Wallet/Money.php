<?php

declare(strict_types=1);

namespace Ledger\Domain\Wallet;

/**
 * Minor units only. Money never touches a float in this codebase, so a
 * balance folded from ten thousand events is still exact to the cent.
 */
final readonly class Money
{
    private function __construct(public int $minorUnits, public string $currency)
    {
        if (1 !== preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new \DomainException(sprintf('"%s" is not an ISO currency code', $currency));
        }
    }

    public static function of(int $minorUnits, string $currency): self
    {
        return new self($minorUnits, $currency);
    }

    public static function zero(string $currency): self
    {
        return new self(0, $currency);
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minorUnits + $other->minorUnits, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minorUnits - $other->minorUnits, $this->currency);
    }

    public function isPositive(): bool
    {
        return $this->minorUnits > 0;
    }

    public function isLessThan(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->minorUnits < $other->minorUnits;
    }

    public function equals(self $other): bool
    {
        return $this->minorUnits === $other->minorUnits && $this->currency === $other->currency;
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new CurrencyMismatch($this->currency, $other->currency);
        }
    }
}
