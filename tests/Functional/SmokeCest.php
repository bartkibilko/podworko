<?php

declare(strict_types=1);

namespace Tests\Functional;

use Tests\Support\FunctionalTester;

final class SmokeCest
{
    public function laravelContainerBoots(FunctionalTester $I): void
    {
        $I->assertSame('testing', config('app.env'));
    }
}
