<?php

namespace Database\Factories;

use App\Models\AcademicPeriod;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    public function definition(): array
    {
        return [
            'student_id' => User::factory()->student(),
            'section_id' => Section::factory(),
            'academic_period_id' => AcademicPeriod::factory(),
            'enrollment_date' => $this->faker->date(),
            'status' => 'active',
            'notes' => null,
        ];
    }
}
