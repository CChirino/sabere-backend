<?php

namespace Database\Factories;

use App\Models\EvaluationItem;
use App\Models\EvaluationPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class EvaluationItemFactory extends Factory
{
    protected $model = EvaluationItem::class;

    public function definition(): array
    {
        return [
            'evaluation_plan_id' => EvaluationPlan::factory(),
            'name' => $this->faker->randomElement(['Examen I', 'Examen II', 'Trabajo escrito', 'Participación', 'Proyecto']),
            'type' => $this->faker->randomElement(['exam', 'quiz', 'project', 'homework', 'participation']),
            'evaluation_mode' => 'quantitative',
            'weight' => $this->faker->randomFloat(2, 10, 40),
            'max_score' => 20,
            'order' => $this->faker->numberBetween(1, 10),
            'evaluation_date' => $this->faker->date(),
        ];
    }

    public function qualitative(): static
    {
        return $this->state(fn (array $attributes) => [
            'evaluation_mode' => 'qualitative',
            'max_score' => null,
        ]);
    }
}
