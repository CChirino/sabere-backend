<?php

namespace Database\Factories;

use App\Models\AcademicPeriod;
use App\Models\AnnualAverage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnnualAverageFactory extends Factory
{
    protected $model = AnnualAverage::class;

    public function definition(): array
    {
        return [
            'student_id' => User::factory()->student(),
            'academic_period_id' => AcademicPeriod::factory(),
            'average_score' => $this->faker->randomFloat(2, 1, 20),
            'letter_grade' => $this->faker->randomElement(['A', 'B', 'C', 'D', 'E']),
            'status' => 'promoted',
        ];
    }
}
