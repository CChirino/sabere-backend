<?php

namespace Database\Factories;

use App\Models\AcademicPeriod;
use App\Models\Grade;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

class SectionFactory extends Factory
{
    protected $model = Section::class;

    public function definition(): array
    {
        return [
            'grade_id' => Grade::factory(),
            'academic_period_id' => AcademicPeriod::factory(),
            'name' => $this->faker->randomElement(['A', 'B', 'C', 'D']),
            'capacity' => 30,
            'status' => true,
        ];
    }
}
