<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use App\Auth\MagicLink;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\AcceptanceTester;

final class MagicLinkLoginCest
{
    public function _after(AcceptanceTester $I): void
    {
        // cleanup:false commits writes, so isolate tests by truncating here.
        foreach (['magic_links', 'users', 'cache', 'cache_locks', 'sessions'] as $table) {
            DB::table($table)->truncate();
        }

        // Undo the process-global fakes some tests install.
        Str::createRandomStringsNormally();
        Carbon::setTestNow();
    }

    private function verifyUrl(string $email, string $token): string
    {
        return '/login/verify?email='.urlencode($email).'&token='.$token;
    }

    /**
     * Drive issuance through the real HTTP request (whose writes are committed
     * and therefore visible to later requests) while pinning the plaintext token
     * via Str's global fake so the test can build the verify link.
     */
    private function requestLinkWithKnownToken(AcceptanceTester $I, string $email, string $token): void
    {
        Str::createRandomStringsUsing(fn (): string => $token);
        $I->amOnPage('/login');
        $I->submitForm('form', ['email' => $email]);
        Str::createRandomStringsNormally();
    }

    public function requestingLinkShowsNeutralPageAndProvisionsUser(AcceptanceTester $I): void
    {
        $I->amOnPage('/login');
        $I->submitForm('form', ['email' => 'new@example.com']);

        $I->seeInCurrentUrl('/login/sent');
        // Open registration: an unknown email is provisioned on request.
        $I->seeRecord('users', ['email' => 'new@example.com']);
        $I->seeRecord('magic_links', ['email' => 'new@example.com']);
    }

    public function responseIsNeutralForUnknownEmail(AcceptanceTester $I): void
    {
        $I->amOnPage('/login');
        $I->submitForm('form', ['email' => 'stranger@example.com']);

        // Same destination as any other address — nothing leaks about existence.
        $I->seeInCurrentUrl('/login/sent');
    }

    public function validLinkLogsUserInAndIsSingleUse(AcceptanceTester $I): void
    {
        $email = 'login@example.com';
        $token = 'valid-known-token';
        $this->requestLinkWithKnownToken($I, $email, $token);

        // GET shows the confirm page; the POST consumes the token and logs in.
        $I->amOnPage($this->verifyUrl($email, $token));
        $I->submitForm('form', []);
        $I->seeAuthentication();
        $I->seeCurrentUrlEquals('/dashboard');
        $I->dontSeeRecord('magic_links', ['email' => $email]);

        // Second attempt with the same (now deleted) token is rejected.
        $I->amOnPage($this->verifyUrl($email, $token));
        $I->submitForm('form', []);
        $I->seeCurrentUrlEquals('/login');
    }

    public function expiredLinkIsRejected(AcceptanceTester $I): void
    {
        $email = 'expired@example.com';
        $token = 'expired-known-token';
        $this->requestLinkWithKnownToken($I, $email, $token);

        // Advance the clock past the token's lifetime.
        Carbon::setTestNow(Carbon::now()->addMinutes(MagicLink::TTL_MINUTES + 1));

        $I->amOnPage($this->verifyUrl($email, $token));
        $I->submitForm('form', []);
        $I->dontSeeAuthentication();
        $I->seeCurrentUrlEquals('/login');
    }

    public function guestIsRedirectedFromProtectedRoute(AcceptanceTester $I): void
    {
        $I->amOnPage('/dashboard');
        $I->seeCurrentUrlEquals('/login');
        $I->dontSeeAuthentication();
    }

    public function excessiveRequestsAreThrottled(AcceptanceTester $I): void
    {
        $email = 'throttle@example.com';

        // The named limiter allows 5 attempts per 15 min per email+IP.
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $I->amOnPage('/login');
            $I->submitForm('form', ['email' => $email]);
        }

        $I->amOnPage('/login');
        $I->submitForm('form', ['email' => $email]);
        $I->seeResponseCodeIs(429);
    }
}
