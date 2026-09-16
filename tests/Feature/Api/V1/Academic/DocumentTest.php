<?php

namespace Tests\Feature\Api\V1\Academic;

use App\Models\DocumentVerification;
use App\Models\StudentScore;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    public function test_student_can_download_own_report_card(): void
    {
        $student = $this->createUser('student');
        $score = StudentScore::factory()->create([
            'student_id' => $student->id,
            'is_final' => true,
        ]);

        $response = $this->actingAs($student)
            ->get("/api/v1/documents/report-card/{$student->id}/{$score->term_id}");

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_student_cannot_download_other_report_card(): void
    {
        $student = $this->createUser('student');
        $other = $this->createUser('student');
        $score = StudentScore::factory()->create([
            'student_id' => $other->id,
            'is_final' => true,
        ]);

        $this->actingAs($student)
            ->get("/api/v1/documents/report-card/{$other->id}/{$score->term_id}")
            ->assertForbidden();
    }

    public function test_document_hash_is_verifiable(): void
    {
        $document = DocumentVerification::factory()->create([
            'expires_at' => now()->addMonth(),
        ]);

        $this->get("/api/v1/documents/verify/{$document->hash}")
            ->assertOk()
            ->assertJsonPath('data.is_valid', true);
    }

    public function test_expired_document_is_not_verifiable(): void
    {
        $document = DocumentVerification::factory()->create([
            'expires_at' => now()->subDay(),
        ]);

        $this->get("/api/v1/documents/verify/{$document->hash}")
            ->assertNotFound();
    }
}
