<?php

declare(strict_types=1);

namespace App\Auth;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Single-use, time-bound login tokens (passwordless magic-link).
 *
 * Mirrors Laravel's password-reset broker: only a hash of the token is stored,
 * the TTL is enforced in code, and single-use is guaranteed by deleting the row
 * on a successful consume.
 */
final class MagicLink
{
    /** Token lifetime in minutes; after this window a link is rejected. */
    public const int TTL_MINUTES = 15;

    private const string TABLE = 'magic_links';

    /**
     * Issue a fresh token for an email, replacing any previous one.
     * Stores only the hash; returns the plaintext token to embed in the link.
     */
    public function issueFor(string $email): string
    {
        $token = Str::random(64);

        DB::table(self::TABLE)->updateOrInsert(
            ['email' => $email],
            ['token' => hash('sha256', $token), 'created_at' => Carbon::now()],
        );

        return $token;
    }

    /**
     * Validate a presented (email, token) pair and consume it.
     *
     * Returns true iff a non-expired token matching the stored hash exists for
     * the email; on success the token row is deleted so it cannot be reused.
     */
    public function consume(string $email, string $token): bool
    {
        $row = DB::table(self::TABLE)->where('email', $email)->first();

        if ($row === null) {
            return false;
        }

        if (! hash_equals((string) $row->token, hash('sha256', $token))) {
            return false;
        }

        if (Carbon::parse($row->created_at)->addMinutes(self::TTL_MINUTES)->isPast()) {
            return false;
        }

        // Single-use: only after every check passes, so a failed attempt never
        // destroys a still-valid token.
        DB::table(self::TABLE)->where('email', $email)->delete();

        return true;
    }
}
