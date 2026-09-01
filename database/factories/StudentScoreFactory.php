<?php

namespace Database\Factories;

use App\Models\StudentScore;
use App\Models\SubjectAssignment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentScoreFactory extends Factory
{
    protected $model = StudentScore::class;

    public function definition(): array
    {
        return [
            'student_id' => User::factory()->student(),
            'subject_assignment_id' => SubjectAssignment::factory(),
            'term_id' => Term::factory(),
            'score' => $this->faker->randomFloat(2, 1, 20),
            'observations' => null,
            'graded_by' => User::factory()->teacher(),
            'graded_at' => now(),
            'is_final' => false,
        ];
    }
}
