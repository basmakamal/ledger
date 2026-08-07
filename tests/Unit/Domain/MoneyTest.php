<?php

declare(strict_types=1);

namespace Ledger\Tests\Unit\Domain;

use Ledger\Domain\Wallet\CurrencyMismatch;
use Ledger\Domain\Wallet\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function testArithmeticStaysExact(): void
    {
        $balance = Money::zero('SAR')
            ->add(Money::of(1050, 'SAR'))
            ->add(Money::of(2575, 'SAR'))
            ->subtract(Money::of(25, 'SAR'));

        self::assertSame(3600, $balance->minorUnits);
    }

    public function testSubtractionCanGoNegativeSoTheAggregateCanDecide(): void
    {
        self::assertSame(-500, Money::of(500, 'SAR')->subtract(Money::of(1000, 'SAR'))->minorUnits);
    }

    public function testMixingCurrenciesIsRejected(): void
    {
        $this->expectException(CurrencyMismatch::class);

        Money::of(100, 'SAR')->add(Money::of(100, 'USD'));
    }

    public function testComparison(): void
    {
        self::assertTrue(Money::of(99, 'SAR')->isLessThan(Money::of(100, 'SAR')));
        self::assertFalse(Money::of(100, 'SAR')->isLessThan(Money::of(100, 'SAR')));
        self::assertTrue(Money::of(1, 'SAR')->isPositive());
        self::assertFalse(Money::zero('SAR')->isPositive());
    }

    public function testEquality(): void
    {
        self::assertTrue(Money::of(100, 'SAR')->equals(Money::of(100, 'SAR')));
        self::assertFalse(Money::of(100, 'SAR')->equals(Money::of(100, 'USD')));
    }

    public function testCurrencyMustBeAnIsoCode(): void
    {
        $this->expectException(\DomainException::class);

        Money::of(100, 'sar');
    }
}
