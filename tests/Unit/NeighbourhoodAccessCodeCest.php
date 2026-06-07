<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\NeighbourhoodAccessCode;
use Tests\Support\UnitTester;

final class NeighbourhoodAccessCodeCest
{
    public function composeHasFixedLengthAndCharset(UnitTester $I): void
    {
        for ($i = 0; $i < 30; $i++) {
            $code = NeighbourhoodAccessCode::compose('Zielona Dolina');
            $I->assertSame(6, strlen($code));
            $I->assertMatchesRegularExpression('/^[A-Z0-9]{6}$/', $code);
        }
    }

    public function composeDerivesPrefixFromName(UnitTester $I): void
    {
        $I->assertStringStartsWith('ZIE', NeighbourhoodAccessCode::compose('Zielona'));
    }

    public function composeAsciiFoldsPolishLetters(UnitTester $I): void
    {
        $I->assertStringStartsWith('LAK', NeighbourhoodAccessCode::compose('Łąka'));
    }

    public function composeFallsBackToRandomForNonAlnumName(UnitTester $I): void
    {
        $code = NeighbourhoodAccessCode::compose('!!!');
        $I->assertSame(6, strlen($code));
        $I->assertMatchesRegularExpression('/^[A-Z0-9]{6}$/', $code);
    }
}
