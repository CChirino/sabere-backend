<?php

namespace Database\Factories;

use App\Models\SubjectAssignment;
use App\Models\Task;
use App\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'subject_assignment_id' => SubjectAssignment::factory(),
            'term_id' => Term::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'instructions' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement(['homework', 'exam', 'quiz', 'project', 'activity']),
            'max_score' => $this->faker->randomFloat(2, 10, 100),
            'weight' => $this->faker->randomFloat(2, 0, 100),
            'due_date' => $this->faker->dateTimeBetween('+1 day', '+1 month'),
            'available_from' => now(),
            'is_published' => true,
            'status' => true,
        ];
    }
}
