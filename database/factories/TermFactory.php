<?php

namespace Database\Factories;

use App\Models\AcademicPeriod;
use App\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;

class TermFactory extends Factory
{
    protected $model = Term::class;

    public function definition(): array
    {
        $academicPeriod = AcademicPeriod::factory()->create();
        $startDate = $this->faker->dateTimeBetween($academicPeriod->start_date, $academicPeriod->end_date);

        return [
            'academic_period_id' => $academicPeriod->id,
            'name' => $this->faker->randomElement(['Primer Lapso', 'Segundo Lapso', 'Tercer Lapso']),
            'number' => $this->faker->numberBetween(1, 3),
            'start_date' => $startDate,
            'end_date' => $this->faker->dateTimeBetween($startDate, $academicPeriod->end_date),
            'weight' => $this->faker->randomFloat(2, 10, 50),
            'status' => true,
        ];
    }
}
