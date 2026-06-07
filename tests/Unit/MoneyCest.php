<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Money;
use InvalidArgumentException;
use Tests\Support\UnitTester;

final class MoneyCest
{
    public function addsAndSubtracts(UnitTester $I): void
    {
        $I->assertSame(300, (new Money(100))->add(new Money(200))->grosze());
        $I->assertSame(-100, (new Money(100))->subtract(new Money(200))->grosze());
    }

    public function multiplies(UnitTester $I): void
    {
        $I->assertSame(750, (new Money(250))->multiply(3)->grosze());
        $I->assertSame(-500, (new Money(250))->multiply(-2)->grosze());
    }

    public function dividesEvenly(UnitTester $I): void
    {
        [$share, $remainder] = (new Money(900))->divide(3);
        $I->assertSame(300, $share);
        $I->assertSame(0, $remainder);
    }

    public function dividesWithRemainder(UnitTester $I): void
    {
        [$share, $remainder] = (new Money(2500))->divide(3);
        $I->assertSame(833, $share);
        $I->assertSame(1, $remainder);
        // Invariant: share * parts + remainder reconstructs the total.
        $I->assertSame(2500, $share * 3 + $remainder);
    }

    public function divideInvariantHoldsForNegative(UnitTester $I): void
    {
        [$share, $remainder] = (new Money(-2500))->divide(3);
        $I->assertSame(-2500, $share * 3 + $remainder);
    }

    public function divideByZeroThrows(UnitTester $I): void
    {
        $I->expectThrowable(InvalidArgumentException::class, function (): void {
            (new Money(100))->divide(0);
        });
    }

    public function divideByNegativeThrows(UnitTester $I): void
    {
        $I->expectThrowable(InvalidArgumentException::class, function (): void {
            (new Money(100))->divide(-2);
        });
    }

    public function equalsComparesGrosze(UnitTester $I): void
    {
        $I->assertTrue((new Money(500))->equals(new Money(500)));
        $I->assertFalse((new Money(500))->equals(new Money(501)));
    }

    public function formatsPolishCurrency(UnitTester $I): void
    {
        $I->assertSame('12,34 zł', (new Money(1234))->format());
        $I->assertSame('0,05 zł', (new Money(5))->format());
        $I->assertSame('0,00 zł', (new Money(0))->format());
        $I->assertSame('-12,34 zł', (new Money(-1234))->format());
    }
}
