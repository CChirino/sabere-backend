<?php

namespace Database\Factories;

use App\Models\SubjectArea;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubjectAreaFactory extends Factory
{
    protected $model = SubjectArea::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'code' => $this->faker->unique()->regexify('[A-Z]{3}'),
            'description' => $this->faker->sentence(),
            'status' => true,
        ];
    }
}
