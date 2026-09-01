<?php

namespace Database\Factories;

use App\Models\StudentGuardian;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentGuardianFactory extends Factory
{
    protected $model = StudentGuardian::class;

    public function definition(): array
    {
        return [
            'guardian_id' => User::factory()->guardian(),
            'student_id' => User::factory()->student(),
            'relationship' => $this->faker->randomElement(['father', 'mother', 'guardian', 'grandparent', 'sibling', 'other']),
            'is_primary' => false,
            'can_pickup' => true,
            'emergency_contact' => true,
            'phone' => $this->faker->phoneNumber(),
            'status' => true,
        ];
    }
}
