<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'due_date' => today(),
            'completed_at' => null,
        ];
    }

    /**
     * Indicate that the task is due on the given number of days from today.
     */
    public function dueInDays(int $days): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => today()->addDays($days),
        ]);
    }

    /**
     * Indicate that the task has been completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed_at' => now(),
        ]);
    }

    /**
     * Indicate that the task belongs to the given project.
     */
    public function forProject(Project $project): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $project->user_id,
            'project_id' => $project->id,
        ]);
    }
}
