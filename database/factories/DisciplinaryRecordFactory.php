<?php

namespace Database\Factories;

use App\Models\DisciplinaryRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DisciplinaryRecord>
 */
class DisciplinaryRecordFactory extends Factory
{
    protected $model = DisciplinaryRecord::class;

    public function definition(): array
    {
        return [
            'student_id' => \App\Models\User::factory()->student(),
            'section_id' => \App\Models\Section::factory(),
            'academic_period_id' => \App\Models\AcademicPeriod::factory(),
            'recorded_by' => \App\Models\User::factory()->teacher(),
            'incident_type_id' => \App\Models\IncidentType::factory(),
            'severity' => fake()->randomElement(['leve', 'moderada', 'grave']),
            'description' => fake()->paragraph(),
            'action_taken' => fake()->optional()->sentence(),
            'date' => fake()->date(),
            'is_private' => false,
        ];
    }
}
