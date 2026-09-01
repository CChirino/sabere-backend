<?php

namespace Database\Factories;

use App\Models\Subject;
use App\Models\SubjectArea;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        return [
            'subject_area_id' => SubjectArea::factory(),
            'name' => $this->faker->unique()->word(),
            'code' => $this->faker->unique()->regexify('[A-Z]{3}[0-9]{3}'),
            'description' => $this->faker->sentence(),
            'status' => true,
        ];
    }
}
