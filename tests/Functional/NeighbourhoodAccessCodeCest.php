<?php

declare(strict_types=1);

namespace Tests\Functional;

use App\Domain\NeighbourhoodAccessCode;
use App\Models\Neighbourhood;
use Tests\Support\FunctionalTester;

final class NeighbourhoodAccessCodeCest
{
    public function generateReturnsCodeNotInDatabase(FunctionalTester $I): void
    {
        $code = NeighbourhoodAccessCode::generate('Zielona Dolina');

        $I->assertMatchesRegularExpression('/^[A-Z0-9]{6}$/', $code);
        $I->dontSeeRecord('neighbourhoods', ['access_code' => $code]);
    }

    public function generateAvoidsExistingCode(FunctionalTester $I): void
    {
        $existing = Neighbourhood::factory()->create(['access_code' => 'ZIEXXX']);

        for ($i = 0; $i < 20; $i++) {
            $I->assertNotSame($existing->access_code, NeighbourhoodAccessCode::generate('Zielona'));
        }
    }
}
