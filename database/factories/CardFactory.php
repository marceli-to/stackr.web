<?php

namespace Database\Factories;

use App\Models\Card;
use App\Models\Stack;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Card>
 */
class CardFactory extends Factory
{
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
            'position' => 'a0',
            'due_at' => fake()->optional()->dateTimeBetween('now', '+1 month'),
        ];
    }
}
