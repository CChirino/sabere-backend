<?php

namespace Database\Factories;

use App\Models\StudentApplication;
use App\Models\StudentApplicationGuardian;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentApplicationGuardianFactory extends Factory
{
    protected $model = StudentApplicationGuardian::class;

    public function definition(): array
    {
        return [
            'student_application_id' => StudentApplication::factory(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'id_number' => $this->faker->unique()->numerify('V########'),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'relationship' => 'mother',
            'is_primary' => true,
            'address' => $this->faker->address(),
        ];
    }
}
