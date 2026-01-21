<?php

namespace Database\Seeders;

use App\Models\EducationLevel;
use Illuminate\Database\Seeder;

class EducationLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $levels = [
            [
                'name' => 'Educación Primaria',
                'code' => 'PRIM',
                'description' => 'Educación primaria de 1º a 6º grado',
            ],
            [
                'name' => 'Educación Secundaria',
                'code' => 'SEC',
                'description' => 'Educación secundaria de 1º a 5º año',
            ],
        ];

        foreach ($levels as $level) {
            EducationLevel::firstOrCreate(
                ['code' => $level['code']],
                $level
            );
        }
    }
}
