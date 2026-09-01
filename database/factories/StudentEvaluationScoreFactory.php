<?php

namespace Database\Factories;

use App\Models\EvaluationItem;
use App\Models\StudentEvaluationScore;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentEvaluationScoreFactory extends Factory
{
    protected $model = StudentEvaluationScore::class;

    public function definition(): array
    {
        return [
            'student_id' => User::factory()->student(),
            'subject_assignment_id' => SubjectAssignment::factory(),
            'evaluation_item_id' => EvaluationItem::factory(),
            'score' => $this->faker->randomFloat(2, 1, 20),
            'letter_grade' => null,
            'graded_by' => User::factory()->teacher(),
            'graded_at' => now(),
            'observations' => null,
        ];
    }

    public function qualitative(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => null,
            'letter_grade' => $this->faker->randomElement(['A', 'B', 'C', 'D', 'E']),
        ]);
    }
}
