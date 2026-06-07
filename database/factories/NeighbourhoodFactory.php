<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Neighbourhood;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Neighbourhood>
 */
final class NeighbourhoodFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->streetName(),
            'access_code' => Str::upper(Str::random(6)),
        ];
    }
}
