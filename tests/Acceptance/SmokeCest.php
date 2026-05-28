<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

final class SmokeCest
{
    public function healthcheckResponds(AcceptanceTester $I): void
    {
        $I->amOnPage('/up');
        $I->seeResponseCodeIs(200);
    }
}
