<?php

namespace Database\Factories;

use App\Models\AcademicPeriod;
use App\Models\Grade;
use App\Models\StudentApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentApplicationFactory extends Factory
{
    protected $model = StudentApplication::class;

    public function definition(): array
    {
        return [
            'academic_period_id' => AcademicPeriod::factory(),
            'grade_id' => Grade::factory(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'birth_date' => $this->faker->date(),
            'gender' => $this->faker->randomElement(['male', 'female', 'other']),
            'id_number' => $this->faker->unique()->numerify('V########'),
            'nationality' => 'Venezolano',
            'address' => $this->faker->address(),
            'current_school' => null,
            'status' => 'pending',
            'notes' => null,
            'rejection_reason' => null,
            'processed_by' => null,
            'processed_at' => null,
            'created_by' => User::factory(),
        ];
    }
}
