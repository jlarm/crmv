<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Task>
 */
final class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'task_type' => fake()->randomElement(['Call', 'Email', 'Meeting']),
            'priority' => fake()->randomElement(['Low', 'Medium', 'High']),
            'status' => 'Open',
            'due_date' => fake()->optional()->date('Y-m-d', '+1 month'),
            'assigned_to' => null,
            'store_id' => null,
            'contact_id' => null,
        ];
    }
}
