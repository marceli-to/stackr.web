<?php

namespace Database\Factories;

use App\Enums\BoardRole;
use App\Models\Board;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Board>
 */
class BoardFactory extends Factory
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
	 * Attach the given user as the board's owner.
	 */
	public function ownedBy(User $user): static
	{
		return $this->hasAttached($user, ['role' => BoardRole::Owner->value], 'members');
	}
}
