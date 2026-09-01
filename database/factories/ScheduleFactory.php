<?php

namespace Database\Factories;

use App\Models\Schedule;
use App\Models\SubjectAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    public function definition(): array
    {
        $startHour = $this->faker->numberBetween(7, 15);
        $startTime = sprintf('%02d:00', $startHour);
        $endTime = sprintf('%02d:00', $startHour + 1);

        return [
            'subject_assignment_id' => SubjectAssignment::factory(),
            'day_of_week' => $this->faker->randomElement(['monday', 'tuesday', 'wednesday', 'thursday', 'friday']),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'classroom' => $this->faker->optional()->word(),
            'notes' => null,
            'status' => true,
        ];
    }
}
