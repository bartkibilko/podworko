<?php

declare(strict_types=1);

namespace Tests\Functional;

use App\Auth\MagicLink;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\FunctionalTester;

final class MagicLinkTokenCest
{
    public function issuedTokenIsStoredHashedNotPlaintext(FunctionalTester $I): void
    {
        $magicLink = new MagicLink;
        $token = $magicLink->issueFor('hash@example.com');

        $I->assertNotEmpty($token);
        // Plaintext must never be persisted; only its hash. Use the Laravel
        // module's record assertions so we read the same connection (and the
        // same open transaction) the code under test wrote through.
        $I->dontSeeRecord('magic_links', ['email' => 'hash@example.com', 'token' => $token]);
        $I->seeRecord('magic_links', ['email' => 'hash@example.com', 'token' => hash('sha256', $token)]);
    }

    public function validTokenIsConsumedAndIsSingleUse(FunctionalTester $I): void
    {
        $magicLink = new MagicLink;
        $token = $magicLink->issueFor('valid@example.com');

        $I->assertTrue($magicLink->consume('valid@example.com', $token), 'fresh token should consume');
        $I->dontSeeRecord('magic_links', ['email' => 'valid@example.com']);
        $I->assertFalse($magicLink->consume('valid@example.com', $token), 'consumed token must not work twice');
    }

    public function expiredTokenIsRejected(FunctionalTester $I): void
    {
        $magicLink = new MagicLink;
        $token = $magicLink->issueFor('expired@example.com');

        DB::table('magic_links')
            ->where('email', 'expired@example.com')
            ->update(['created_at' => Carbon::now()->subMinutes(MagicLink::TTL_MINUTES + 1)]);

        $I->assertFalse($magicLink->consume('expired@example.com', $token));
    }

    public function wrongTokenIsRejected(FunctionalTester $I): void
    {
        $magicLink = new MagicLink;
        $magicLink->issueFor('wrong@example.com');

        $I->assertFalse($magicLink->consume('wrong@example.com', 'not-the-real-token'));
    }
}
