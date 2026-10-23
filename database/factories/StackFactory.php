<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Stack;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stack>
 */
class StackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => fake()->word(),
            'position' => 'a0',
        ];
    }
}
