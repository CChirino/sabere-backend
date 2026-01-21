<?php

namespace Database\Seeders;

use App\Models\AcademicPeriod;
use App\Models\Term;
use Illuminate\Database\Seeder;

class TermSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener todos los períodos académicos activos
        $periods = AcademicPeriod::where('status', true)->get();

        foreach ($periods as $period) {
            // Verificar si ya tiene lapsos
            if ($period->terms()->count() > 0) {
                continue;
            }

            $startDate = \Carbon\Carbon::parse($period->start_date);
            $endDate = \Carbon\Carbon::parse($period->end_date);
            $totalDays = $startDate->diffInDays($endDate);
            $termDays = intval($totalDays / 3);

            // Crear 3 lapsos para cada período
            for ($i = 1; $i <= 3; $i++) {
                $termStart = $startDate->copy()->addDays(($i - 1) * $termDays);
                $termEnd = $i === 3 
                    ? $endDate 
                    : $startDate->copy()->addDays($i * $termDays)->subDay();

                Term::create([
                    'academic_period_id' => $period->id,
                    'name' => "Lapso $i",
                    'number' => $i,
                    'start_date' => $termStart->format('Y-m-d'),
                    'end_date' => $termEnd->format('Y-m-d'),
                    'weight' => 33.33,
                    'status' => true,
                ]);
            }
        }
    }
}
