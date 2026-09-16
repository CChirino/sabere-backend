<?php

namespace Database\Factories;

use App\Models\IncidentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IncidentType>
 */
class IncidentTypeFactory extends Factory
{
    protected $model = IncidentType::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'default_severity' => fake()->randomElement(['leve', 'moderada', 'grave']),
            'requires_notification' => fake()->boolean(),
            'description' => fake()->optional()->paragraph(),
            'is_active' => true,
        ];
    }
}
