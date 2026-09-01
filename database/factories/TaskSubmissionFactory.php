<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskSubmissionFactory extends Factory
{
    protected $model = TaskSubmission::class;

    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'student_id' => User::factory()->student(),
            'content' => $this->faker->paragraph(),
            'file_path' => null,
            'submitted_at' => now(),
            'score' => null,
            'feedback' => null,
            'graded_by' => null,
            'graded_at' => null,
            'status' => $this->faker->randomElement(['submitted', 'late', 'graded', 'returned']),
        ];
    }
}
