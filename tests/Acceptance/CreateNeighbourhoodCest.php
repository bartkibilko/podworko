<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Support\AcceptanceTester;

final class CreateNeighbourhoodCest
{
    public function _after(AcceptanceTester $I): void
    {
        // cleanup:false commits writes; truncate (CASCADE on Postgres) to isolate.
        foreach (['memberships', 'households', 'neighbourhoods', 'users', 'sessions', 'cache', 'cache_locks'] as $table) {
            DB::table($table)->truncate();
        }
    }

    public function foundsNeighbourhoodAndBecomesFounder(AcceptanceTester $I): void
    {
        $user = User::factory()->create();
        $I->amLoggedAs($user);

        $I->amOnPage('/neighbourhoods/create');
        $I->submitForm('form', ['name' => 'Zielona Dolina']);

        $I->see('Po zapisie kod nie może być zmieniony');
        $code = $I->grabValueFrom('input[name=access_code]');

        $I->click('Zapisz osiedle');

        $I->seeInCurrentUrl('/dashboard');
        $I->seeRecord('neighbourhoods', ['name' => 'Zielona Dolina', 'access_code' => $code]);
        $I->seeRecord('memberships', ['user_id' => $user->id, 'role' => 'founder']);
        // Dashboard lists the new neighbourhood with its code.
        $I->see('Zielona Dolina');
        $I->see($code);
    }

    public function regenerateProducesADifferentCode(AcceptanceTester $I): void
    {
        $I->amLoggedAs(User::factory()->create());

        $I->amOnPage('/neighbourhoods/create');
        $I->submitForm('form', ['name' => 'Zielona Dolina']);
        $first = $I->grabValueFrom('input[name=access_code]');

        $I->click('Generuj nowy kod');
        $second = $I->grabValueFrom('input[name=access_code]');

        $I->assertNotSame($first, $second);
    }

    public function guestIsRedirectedFromCreate(AcceptanceTester $I): void
    {
        $I->amOnPage('/neighbourhoods/create');
        $I->seeCurrentUrlEquals('/login');
        $I->dontSeeAuthentication();
    }
}
