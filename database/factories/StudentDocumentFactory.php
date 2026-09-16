<?php

namespace Database\Factories;

use App\Models\StudentDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentDocumentFactory extends Factory
{
    protected $model = StudentDocument::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->withRole('student'),
            'type' => 'carnet',
            'path' => 'students/1/documents/test.pdf',
            'original_name' => 'carnet.pdf',
            'mime' => 'application/pdf',
            'description' => null,
            'is_verified' => false,
        ];
    }
}
