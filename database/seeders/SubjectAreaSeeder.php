<?php

namespace Database\Seeders;

use App\Models\SubjectArea;
use Illuminate\Database\Seeder;

class SubjectAreaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $areas = [
            [
                'name' => 'Lengua y Comunicación',
                'code' => 'LENG',
                'description' => 'Área de lenguaje, lectura y escritura',
            ],
            [
                'name' => 'Matemática',
                'code' => 'MAT',
                'description' => 'Área de matemáticas y lógica',
            ],
            [
                'name' => 'Ciencias Naturales',
                'code' => 'CNAT',
                'description' => 'Área de ciencias naturales y ambiente',
            ],
            [
                'name' => 'Ciencias Sociales',
                'code' => 'CSOC',
                'description' => 'Área de ciencias sociales, historia y geografía',
            ],
            [
                'name' => 'Educación Estética',
                'code' => 'EST',
                'description' => 'Área de arte, música y expresión artística',
            ],
            [
                'name' => 'Educación Física',
                'code' => 'EFIS',
                'description' => 'Área de educación física y deportes',
            ],
            [
                'name' => 'Tecnología',
                'code' => 'TEC',
                'description' => 'Área de computación y tecnología',
            ],
            [
                'name' => 'Formación Ciudadana',
                'code' => 'FCIU',
                'description' => 'Área de valores, ética y ciudadanía',
            ],
        ];

        foreach ($areas as $area) {
            SubjectArea::firstOrCreate(
                ['code' => $area['code']],
                $area
            );
        }
    }
}
