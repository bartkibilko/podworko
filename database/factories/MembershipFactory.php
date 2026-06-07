<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Household;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Membership>
 */
final class MembershipFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Build a consistent graph: the membership's neighbourhood matches its
        // household's neighbourhood.
        $household = Household::factory()->create();

        return [
            'user_id' => User::factory(),
            'neighbourhood_id' => $household->neighbourhood_id,
            'household_id' => $household->id,
            'role' => Role::Owner,
            'requested_household_name' => null,
        ];
    }

    public function founder(): static
    {
        return $this->state(fn (array $attributes): array => ['role' => Role::Founder]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => Role::Pending,
            'household_id' => null,
            'requested_household_name' => 'Nowy dom',
        ]);
    }
}
