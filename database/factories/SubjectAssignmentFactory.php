<?php

namespace Database\Factories;

use App\Models\AcademicPeriod;
use App\Models\Section;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubjectAssignmentFactory extends Factory
{
    protected $model = SubjectAssignment::class;

    public function definition(): array
    {
        return [
            'teacher_id' => User::factory()->teacher(),
            'subject_id' => Subject::factory(),
            'section_id' => Section::factory(),
            'academic_period_id' => AcademicPeriod::factory(),
            'status' => true,
        ];
    }
}
