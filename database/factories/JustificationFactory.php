<?php

namespace Database\Factories;

use App\Models\Justification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Justification>
 */
class JustificationFactory extends Factory
{
    protected $model = Justification::class;

    public function definition(): array
    {
        return [
            'student_id' => \App\Models\User::factory()->student(),
            'guardian_id' => \App\Models\User::factory()->guardian(),
            'academic_period_id' => \App\Models\AcademicPeriod::factory(),
            'start_date' => fake()->date(),
            'end_date' => fake()->date(),
            'reason' => fake()->paragraph(),
            'document_path' => null,
            'status' => 'pending',
            'reviewed_by' => null,
            'review_notes' => null,
            'reviewed_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'reviewed_at' => now(),
        ]);
    }
}
