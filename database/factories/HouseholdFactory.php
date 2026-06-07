<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Household;
use App\Models\Neighbourhood;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Household>
 */
final class HouseholdFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'neighbourhood_id' => Neighbourhood::factory(),
            'label' => fake()->lastName().' household',
        ];
    }
}
