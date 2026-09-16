<?php

namespace Database\Factories;

use App\Models\DocumentVerification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DocumentVerificationFactory extends Factory
{
    protected $model = DocumentVerification::class;

    public function definition(): array
    {
        return [
            'document_type' => $this->faker->randomElement(['report_card', 'study_certificate', 'enrollment_certificate']),
            'document_id' => $this->faker->randomNumber(),
            'hash' => (string) Str::uuid(),
            'generated_by' => User::factory(),
            'expires_at' => null,
            'metadata' => [],
        ];
    }
}
