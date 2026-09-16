<?php

namespace Database\Factories;

use App\Models\StudentApplication;
use App\Models\StudentApplicationDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentApplicationDocumentFactory extends Factory
{
    protected $model = StudentApplicationDocument::class;

    public function definition(): array
    {
        return [
            'student_application_id' => StudentApplication::factory(),
            'type' => 'partida',
            'path' => 'admissions/1/test.pdf',
            'original_name' => 'partida.pdf',
            'mime' => 'application/pdf',
            'description' => null,
        ];
    }
}
