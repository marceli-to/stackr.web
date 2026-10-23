<?php

namespace Database\Factories;

use App\Models\Card;
use App\Models\Stack;
use App\Support\FractionalIndex;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Card>
 */
class CardFactory extends Factory
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
            'stack_id' => Stack::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'position' => fn () => self::$lastPosition = FractionalIndex::between(self::$lastPosition, null),
            'due_at' => fake()->optional()->dateTimeBetween('now', '+1 month'),
        ];
    }
}
