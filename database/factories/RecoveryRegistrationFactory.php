<?php

namespace Database\Factories;

use App\Models\AcademicPeriod;
use App\Models\RecoveryRegistration;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecoveryRegistrationFactory extends Factory
{
    protected $model = RecoveryRegistration::class;

    public function definition(): array
    {
        return [
            'student_id' => User::factory()->student(),
            'subject_id' => Subject::factory(),
            'academic_period_id' => AcademicPeriod::factory(),
            'status' => 'pending',
            'recovery_score' => null,
        ];
    }
}
