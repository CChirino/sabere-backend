<?php

namespace Database\Factories;

use App\Models\AcademicPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

class AcademicPeriodFactory extends Factory
{
    protected $model = AcademicPeriod::class;

    private static int $sequenceYear = 2020;

    public function definition(): array
    {
        $startYear = self::$sequenceYear++;

        return [
            'name' => "Año Escolar {$startYear}-".($startYear + 1),
            'code' => (string) $startYear,
            'school_year' => "{$startYear}-".($startYear + 1),
            'start_date' => "{$startYear}-09-01",
            'end_date' => ($startYear + 1).'-07-31',
            'status' => true,
        ];
    }
}
