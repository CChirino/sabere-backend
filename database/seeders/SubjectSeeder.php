<?php

namespace Database\Seeders;

use App\Models\Subject;
use App\Models\SubjectArea;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $areas = SubjectArea::pluck('id', 'code');

        $subjects = [
            // Lengua y Comunicación
            [
                'subject_area_code' => 'LENG',
                'name' => 'Castellano y Lectoescritura',
                'code' => 'CAST-LE',
                'description' => 'Castellano con énfasis en lectoescritura para grados iniciales',
            ],
            [
                'subject_area_code' => 'LENG',
                'name' => 'Castellano',
                'code' => 'CAST',
                'description' => 'Lengua castellana, gramática, redacción y análisis de textos',
            ],

            // Matemática
            [
                'subject_area_code' => 'MAT',
                'name' => 'Matemática',
                'code' => 'MAT',
                'description' => 'Matemáticas: aritmética, álgebra, geometría y estadística',
            ],

            // Ciencias Naturales
            [
                'subject_area_code' => 'CNAT',
                'name' => 'Ciencias Naturales',
                'code' => 'CNAT',
                'description' => 'Ciencias naturales, biología, física y química básica',
            ],

            // Ciencias Sociales
            [
                'subject_area_code' => 'CSOC',
                'name' => 'Ciencias Sociales',
                'code' => 'CSOC',
                'description' => 'Historia, geografía y estudios sociales',
            ],

            // Educación Estética
            [
                'subject_area_code' => 'EST',
                'name' => 'Educación Estética',
                'code' => 'EEST',
                'description' => 'Arte, música y expresión artística',
            ],

            // Educación Física
            [
                'subject_area_code' => 'EFIS',
                'name' => 'Educación Física',
                'code' => 'EFIS',
                'description' => 'Educación física, deportes y recreación',
            ],

            // Tecnología
            [
                'subject_area_code' => 'TEC',
                'name' => 'Computación',
                'code' => 'COMP',
                'description' => 'Computación y herramientas tecnológicas',
            ],

            // Formación Ciudadana
            [
                'subject_area_code' => 'FCIU',
                'name' => 'Formación en Valores',
                'code' => 'FVAL',
                'description' => 'Formación en valores, ética y orientación',
            ],
            [
                'subject_area_code' => 'FCIU',
                'name' => 'Ética y Valores',
                'code' => 'ETVAL',
                'description' => 'Ética, valores y convivencia',
            ],
            [
                'subject_area_code' => 'FCIU',
                'name' => 'Valores y Ciudadanía',
                'code' => 'VCIU',
                'description' => 'Valores, ciudadanía y competencias ciudadanas',
            ],
        ];

        foreach ($subjects as $subject) {
            $areaId = $areas[$subject['subject_area_code']] ?? null;
            
            if (!$areaId) {
                $this->command->warn("Área {$subject['subject_area_code']} no encontrada para {$subject['name']}");
                continue;
            }

            Subject::firstOrCreate(
                ['code' => $subject['code']],
                [
                    'subject_area_id' => $areaId,
                    'name' => $subject['name'],
                    'code' => $subject['code'],
                    'description' => $subject['description'],
                ]
            );
        }
    }
}
