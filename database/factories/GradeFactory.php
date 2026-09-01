<?php

namespace Database\Factories;

use App\Models\EducationLevel;
use App\Models\Grade;
use Illuminate\Database\Eloquent\Factories\Factory;

class GradeFactory extends Factory
{
    protected $model = Grade::class;

    public function definition(): array
    {
        return [
            'education_level_id' => EducationLevel::factory(),
            'name' => $this->faker->numberBetween(1, 6).'° Grado',
            'numeric_equivalent' => $this->faker->numberBetween(1, 11),
            'status' => true,
        ];
    }
}
