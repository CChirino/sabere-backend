<?php

namespace Database\Seeders;

use App\Models\EducationLevel;
use App\Models\Grade;
use Illuminate\Database\Seeder;

class GradeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $primaria = EducationLevel::where('code', 'PRIM')->first();

        if (!$primaria) {
            $this->command->error('Nivel educativo PRIM no encontrado. Ejecuta EducationLevelSeeder primero.');
            return;
        }

        $grades = [
            ['name' => 'Primer Grado', 'numeric_equivalent' => 1],
            ['name' => 'Segundo Grado', 'numeric_equivalent' => 2],
            ['name' => 'Tercer Grado', 'numeric_equivalent' => 3],
            ['name' => 'Cuarto Grado', 'numeric_equivalent' => 4],
            ['name' => 'Quinto Grado', 'numeric_equivalent' => 5],
            ['name' => 'Sexto Grado', 'numeric_equivalent' => 6],
        ];

        foreach ($grades as $grade) {
            Grade::firstOrCreate(
                [
                    'education_level_id' => $primaria->id,
                    'numeric_equivalent' => $grade['numeric_equivalent'],
                ],
                [
                    'name' => $grade['name'],
                    'education_level_id' => $primaria->id,
                    'numeric_equivalent' => $grade['numeric_equivalent'],
                ]
            );
        }
    }
}
