<?php

namespace Database\Factories;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(asText: true),
            'description' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Attach the given user as the project's owner.
     */
    public function ownedBy(User $user): static
    {
        return $this->hasAttached($user, ['role' => ProjectRole::Owner->value], 'members');
    }
}
