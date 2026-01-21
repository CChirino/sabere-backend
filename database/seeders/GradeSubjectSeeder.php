<?php

namespace Database\Seeders;

use App\Models\EducationLevel;
use App\Models\Grade;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GradeSubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schoolYear = '2024-2025';
        
        $primaria = EducationLevel::where('code', 'PRIM')->first();
        
        if (!$primaria) {
            $this->command->error('Nivel educativo PRIM no encontrado.');
            return;
        }

        $grades = Grade::where('education_level_id', $primaria->id)
            ->pluck('id', 'numeric_equivalent');
        
        $subjects = Subject::pluck('id', 'code');

        // Definición de materias por grado
        // Formato: 'SUBJECT_CODE' => hours_per_week
        $gradeSubjects = [
            // PRIMER GRADO (1.º)
            1 => [
                'CAST-LE' => ['hours' => 6, 'optional' => false],
                'MAT' => ['hours' => 5, 'optional' => false],
                'CNAT' => ['hours' => 3, 'optional' => false],
                'CSOC' => ['hours' => 3, 'optional' => false],
                'EEST' => ['hours' => 2, 'optional' => false],
                'EFIS' => ['hours' => 2, 'optional' => false],
                'COMP' => ['hours' => 2, 'optional' => false],
                'FVAL' => ['hours' => 2, 'optional' => false],
            ],

            // SEGUNDO GRADO (2.º)
            2 => [
                'CAST' => ['hours' => 6, 'optional' => false],
                'MAT' => ['hours' => 5, 'optional' => false],
                'CNAT' => ['hours' => 3, 'optional' => false],
                'CSOC' => ['hours' => 3, 'optional' => false],
                'ETVAL' => ['hours' => 2, 'optional' => false],
                'EEST' => ['hours' => 2, 'optional' => false],
                'EFIS' => ['hours' => 2, 'optional' => false],
            ],

            // TERCER GRADO (3.º)
            3 => [
                'CAST' => ['hours' => 6, 'optional' => false],
                'MAT' => ['hours' => 5, 'optional' => false],
                'CNAT' => ['hours' => 3, 'optional' => false],
                'CSOC' => ['hours' => 3, 'optional' => false],
                'EEST' => ['hours' => 2, 'optional' => false],
                'EFIS' => ['hours' => 2, 'optional' => false],
                'VCIU' => ['hours' => 2, 'optional' => false],
            ],

            // CUARTO GRADO (4.º)
            4 => [
                'CAST' => ['hours' => 5, 'optional' => false],
                'MAT' => ['hours' => 5, 'optional' => false],
                'CNAT' => ['hours' => 4, 'optional' => false],
                'CSOC' => ['hours' => 4, 'optional' => false],
                'EEST' => ['hours' => 2, 'optional' => false],
                'EFIS' => ['hours' => 2, 'optional' => false],
                'VCIU' => ['hours' => 2, 'optional' => false],
            ],

            // QUINTO GRADO (5.º)
            5 => [
                'CAST' => ['hours' => 5, 'optional' => false],
                'MAT' => ['hours' => 5, 'optional' => false],
                'CNAT' => ['hours' => 4, 'optional' => false],
                'CSOC' => ['hours' => 4, 'optional' => false],
                'EEST' => ['hours' => 2, 'optional' => false],
                'EFIS' => ['hours' => 2, 'optional' => false],
                'COMP' => ['hours' => 2, 'optional' => false],
                'VCIU' => ['hours' => 2, 'optional' => false],
            ],

            // SEXTO GRADO (6.º)
            6 => [
                'CAST' => ['hours' => 5, 'optional' => false],
                'MAT' => ['hours' => 5, 'optional' => false],
                'CNAT' => ['hours' => 4, 'optional' => false],
                'CSOC' => ['hours' => 4, 'optional' => false],
                'EEST' => ['hours' => 2, 'optional' => false],
                'EFIS' => ['hours' => 2, 'optional' => false],
                'COMP' => ['hours' => 2, 'optional' => false],
                'VCIU' => ['hours' => 2, 'optional' => false],
            ],
        ];

        foreach ($gradeSubjects as $gradeNumber => $subjectList) {
            $gradeId = $grades[$gradeNumber] ?? null;
            
            if (!$gradeId) {
                $this->command->warn("Grado {$gradeNumber} no encontrado.");
                continue;
            }

            foreach ($subjectList as $subjectCode => $config) {
                $subjectId = $subjects[$subjectCode] ?? null;
                
                if (!$subjectId) {
                    $this->command->warn("Materia {$subjectCode} no encontrada para grado {$gradeNumber}.");
                    continue;
                }

                // Verificar si ya existe
                $exists = DB::table('grade_subject')
                    ->where('grade_id', $gradeId)
                    ->where('subject_id', $subjectId)
                    ->where('school_year', $schoolYear)
                    ->exists();

                if (!$exists) {
                    DB::table('grade_subject')->insert([
                        'grade_id' => $gradeId,
                        'subject_id' => $subjectId,
                        'school_year' => $schoolYear,
                        'hours_per_week' => $config['hours'],
                        'is_optional' => $config['optional'],
                        'status' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        $this->command->info('Materias asignadas a grados correctamente.');
    }
}
