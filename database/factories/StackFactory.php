<?php

namespace Database\Factories;

use App\Models\Board;
use App\Models\Stack;
use App\Support\FractionalIndex;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stack>
 */
class StackFactory extends Factory
{
	/**
	 * Each new model sorts after the previous one, like appending in the app.
	 */
	private static ?string $lastPosition = null;

	/**
	 * Define the model's default state.
	 *
	 * @return array<string, mixed>
	 */
	public function definition(): array
	{
		return [
			'board_id' => Board::factory(),
			'name' => fake()->word(),
			'position' => fn () => self::$lastPosition = FractionalIndex::between(self::$lastPosition, null),
		];
	}
}
