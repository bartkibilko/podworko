<?php

declare(strict_types=1);

namespace App\Domain;

use App\Models\Neighbourhood;
use Illuminate\Support\Str;

/**
 * Generates a short neighbourhood join code (FR-002): a name-derived prefix
 * plus a random suffix, A-Z0-9, exactly MAX_LENGTH chars, unique.
 */
final class NeighbourhoodAccessCode
{
    public const int MAX_LENGTH = 6;

    /** Generate a code that is not yet taken (retries on collision). */
    public static function generate(string $name): string
    {
        do {
            $code = self::compose($name);
        } while (Neighbourhood::query()->where('access_code', $code)->exists());

        return $code;
    }

    /** One candidate: ASCII-folded, uppercased prefix from the name + random suffix. */
    public static function compose(string $name): string
    {
        $prefix = Str::of($name)
            ->ascii()
            ->upper()
            ->replaceMatches('/[^A-Z0-9]/', '')
            ->substr(0, 3)
            ->value();

        $suffix = Str::upper(Str::random(self::MAX_LENGTH - strlen($prefix)));

        return $prefix.$suffix;
    }
}
