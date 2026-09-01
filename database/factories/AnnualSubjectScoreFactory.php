<?php

namespace Database\Factories;

use App\Models\AcademicPeriod;
use App\Models\AnnualSubjectScore;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnnualSubjectScoreFactory extends Factory
{
    protected $model = AnnualSubjectScore::class;

    public function definition(): array
    {
        return [
            'student_id' => User::factory()->student(),
            'subject_id' => Subject::factory(),
            'academic_period_id' => AcademicPeriod::factory(),
            'final_score' => $this->faker->randomFloat(2, 1, 20),
            'letter_grade' => $this->faker->randomElement(['A', 'B', 'C', 'D', 'E']),
            'status' => 'promoted',
            'is_pending' => false,
        ];
    }
}
