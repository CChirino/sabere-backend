<?php

namespace Database\Factories;

use App\Models\AcademicPeriod;
use App\Models\Grade;
use App\Models\StudentPromotion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentPromotionFactory extends Factory
{
    protected $model = StudentPromotion::class;

    public function definition(): array
    {
        return [
            'student_id' => User::factory()->student(),
            'academic_period_id' => AcademicPeriod::factory(),
            'from_grade_id' => Grade::factory(),
            'to_grade_id' => Grade::factory(),
            'status' => $this->faker->randomElement(['promoted', 'repeating', 'conditional']),
            'decision' => $this->faker->sentence(),
            'decided_by' => User::factory(),
            'decided_at' => now(),
        ];
    }
}
