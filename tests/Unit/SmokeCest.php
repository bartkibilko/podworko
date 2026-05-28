<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\Support\UnitTester;

final class SmokeCest
{
    public function suiteBoots(UnitTester $I): void
    {
        $I->assertSame(2, 1 + 1);
    }
}
