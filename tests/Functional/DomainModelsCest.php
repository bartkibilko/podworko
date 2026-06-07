<?php

declare(strict_types=1);

namespace Tests\Functional;

use App\Enums\Role;
use App\Models\Household;
use App\Models\Membership;
use App\Models\Neighbourhood;
use Illuminate\Database\QueryException;
use Tests\Support\FunctionalTester;

final class DomainModelsCest
{
    public function relationshipsTraverse(FunctionalTester $I): void
    {
        $membership = Membership::factory()->create();

        $I->assertNotNull($membership->user);
        $I->assertNotNull($membership->neighbourhood);
        $I->assertNotNull($membership->household);
        // Household belongs to the same neighbourhood as the membership.
        $I->assertSame($membership->neighbourhood_id, $membership->household->neighbourhood_id);
        // Reverse relations.
        $I->assertTrue($membership->neighbourhood->memberships->contains($membership));
        $I->assertTrue($membership->neighbourhood->households->contains($membership->household));
    }

    public function roleIsCastToEnum(FunctionalTester $I): void
    {
        $membership = Membership::factory()->create();

        $I->assertInstanceOf(Role::class, $membership->role);
        $I->assertSame(Role::Owner, $membership->role);
    }

    public function pendingMembershipPersistsWithoutHousehold(FunctionalTester $I): void
    {
        $membership = Membership::factory()->pending()->create();

        $I->assertNull($membership->household_id);
        $I->assertSame(Role::Pending, $membership->role);
        $I->seeRecord('memberships', [
            'id' => $membership->id,
            'household_id' => null,
            'requested_household_name' => 'Nowy dom',
        ]);
    }

    public function neighbourhoodDeleteIsRestrictedWhileHouseholdsExist(FunctionalTester $I): void
    {
        $household = Household::factory()->create();
        $neighbourhood = $household->neighbourhood;

        // restrictOnDelete → FK violation, history preserved (FR-024).
        $I->expectThrowable(QueryException::class, function () use ($neighbourhood): void {
            $neighbourhood->delete();
        });
    }

    public function onlyOneFounderPerNeighbourhood(FunctionalTester $I): void
    {
        $neighbourhood = Neighbourhood::factory()->create();

        Membership::factory()->founder()->create([
            'neighbourhood_id' => $neighbourhood->id,
            'household_id' => null,
        ]);

        // Partial unique index rejects a second founder in the same neighbourhood.
        $I->expectThrowable(QueryException::class, function () use ($neighbourhood): void {
            Membership::factory()->founder()->create([
                'neighbourhood_id' => $neighbourhood->id,
                'household_id' => null,
            ]);
        });
    }
}
