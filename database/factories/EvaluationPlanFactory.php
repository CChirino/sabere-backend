<?php

namespace Database\Factories;

use App\Models\AcademicPeriod;
use App\Models\EvaluationPlan;
use App\Models\Grade;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;

class EvaluationPlanFactory extends Factory
{
    protected $model = EvaluationPlan::class;

    public function definition(): array
    {
        return [
            'academic_period_id' => AcademicPeriod::factory(),
            'term_id' => Term::factory(),
            'subject_id' => Subject::factory(),
            'grade_id' => Grade::factory(),
            'section_id' => null,
            'status' => 'draft',
            'notes' => null,
        ];
    }

    public function forSection(Section $section): static
    {
        return $this->state(fn (array $attributes) => [
            'grade_id' => $section->grade_id,
            'section_id' => $section->id,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);
    }
}
