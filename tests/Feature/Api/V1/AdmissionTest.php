<?php

namespace Tests\Feature\Api\V1;

use App\Models\AcademicPeriod;
use App\Models\Grade;
use App\Models\Section;
use App\Models\StudentApplication;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdmissionTest extends TestCase
{
    public function test_staff_can_create_admission(): void
    {
        $coordinator = $this->createUser('coordinator');
        $period = AcademicPeriod::factory()->create();
        $grade = Grade::factory()->create();

        $payload = [
            'academic_period_id' => $period->id,
            'grade_id' => $grade->id,
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'birth_date' => '2015-05-10',
            'gender' => 'male',
            'id_number' => 'V12345678',
            'guardians' => [
                [
                    'first_name' => 'María',
                    'last_name' => 'Pérez',
                    'id_number' => 'V87654321',
                    'email' => 'maria@example.com',
                    'phone' => '0412-1234567',
                    'relationship' => 'mother',
                    'is_primary' => true,
                ],
            ],
        ];

        $this->actingAs($coordinator)
            ->postJson('/api/v1/admissions', $payload)
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Admisión creada exitosamente')
            ->assertJsonPath('data.first_name', 'Juan');

        $this->assertDatabaseHas('student_applications', [
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'status' => 'pending',
            'created_by' => $coordinator->id,
        ]);
    }

    public function test_guardian_cannot_create_admission(): void
    {
        $guardian = $this->createUser('guardian');
        $period = AcademicPeriod::factory()->create();
        $grade = Grade::factory()->create();

        $this->actingAs($guardian)
            ->postJson('/api/v1/admissions', [
                'academic_period_id' => $period->id,
                'grade_id' => $grade->id,
                'first_name' => 'Juan',
                'last_name' => 'Pérez',
                'birth_date' => '2015-05-10',
                'gender' => 'male',
                'id_number' => 'V12345678',
                'guardians' => [],
            ])
            ->assertForbidden();
    }

    public function test_staff_can_approve_admission_and_create_enrollment(): void
    {
        $coordinator = $this->createUser('coordinator');
        $application = StudentApplication::factory()->create(['status' => 'pending']);
        $application->guardians()->save(\App\Models\StudentApplicationGuardian::factory()->make());

        $section = Section::factory()->create([
            'academic_period_id' => $application->academic_period_id,
            'grade_id' => $application->grade_id,
            'capacity' => 30,
        ]);

        $this->actingAs($coordinator)
            ->postJson("/api/v1/admissions/{$application->id}/approve", [
                'section_id' => $section->id,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Admisión aprobada y matrícula creada exitosamente')
            ->assertJsonPath('data.section_id', $section->id);

        $application->refresh();
        $this->assertEquals('approved', $application->status);

        $this->assertDatabaseHas('enrollments', [
            'academic_period_id' => $application->academic_period_id,
            'section_id' => $section->id,
            'status' => 'active',
        ]);
    }

    public function test_staff_can_reject_admission(): void
    {
        $coordinator = $this->createUser('coordinator');
        $application = StudentApplication::factory()->create(['status' => 'pending']);

        $this->actingAs($coordinator)
            ->postJson("/api/v1/admissions/{$application->id}/reject", [
                'rejection_reason' => 'Documentos incompletos',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Admisión rechazada exitosamente')
            ->assertJsonPath('data.status', 'rejected');

        $this->assertEquals('rejected', $application->fresh()->status);
    }

    public function test_suggested_sections_are_returned(): void
    {
        $coordinator = $this->createUser('coordinator');
        $application = StudentApplication::factory()->create();
        Section::factory()->create([
            'academic_period_id' => $application->academic_period_id,
            'grade_id' => $application->grade_id,
            'capacity' => 30,
        ]);

        $this->actingAs($coordinator)
            ->getJson("/api/v1/admissions/{$application->id}/suggest-sections")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Secciones sugeridas obtenidas exitosamente')
            ->assertJsonCount(1, 'data');
    }

    public function test_guardian_can_update_student_profile(): void
    {
        $guardian = $this->createUser('guardian');
        $student = $this->createUser('student');
        $guardian->students()->attach($student->id, [
            'relationship' => 'mother',
            'is_primary' => true,
        ]);

        $this->actingAs($guardian)
            ->putJson("/api/v1/students/{$student->id}/profile", [
                'emergency_contact_name' => 'María Pérez',
                'emergency_contact_phone' => '0412-1234567',
                'allergies' => 'Polen',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Perfil estudiantil actualizado exitosamente')
            ->assertJsonPath('data.allergies', 'Polen');

        $this->assertDatabaseHas('student_profiles', [
            'user_id' => $student->id,
            'emergency_contact_name' => 'María Pérez',
            'allergies' => 'Polen',
        ]);
    }

    public function test_admissions_list_and_show_use_standard_responses(): void
    {
        $coordinator = $this->createUser('coordinator');
        $application = StudentApplication::factory()->create();

        $this->actingAs($coordinator)
            ->getJson('/api/v1/admissions?per_page=5')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Admisiones obtenidas exitosamente')
            ->assertJsonPath('per_page', 5)
            ->assertJsonStructure(['data', 'current_page', 'last_page', 'total']);

        $this->actingAs($coordinator)
            ->getJson("/api/v1/admissions/{$application->id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Admisión obtenida exitosamente')
            ->assertJsonPath('data.id', $application->id);
    }

    public function test_student_profile_and_documents_use_standard_responses(): void
    {
        Storage::fake('public');

        $coordinator = $this->createUser('coordinator');
        $student = $this->createUser('student');

        $this->actingAs($coordinator)
            ->getJson("/api/v1/students/{$student->id}/profile")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Perfil estudiantil obtenido exitosamente')
            ->assertJsonStructure(['data' => ['profile', 'documents']]);

        $response = $this->actingAs($coordinator)
            ->post("/api/v1/students/{$student->id}/documents", [
                'file' => UploadedFile::fake()->create('partida.pdf', 100, 'application/pdf'),
                'type' => 'birth_certificate',
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Documento estudiantil creado exitosamente');

        $documentId = $response->json('data.id');

        $this->actingAs($coordinator)
            ->postJson("/api/v1/students/{$student->id}/documents/{$documentId}/verify")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Documento estudiantil verificado exitosamente')
            ->assertJsonPath('data.is_verified', true);
    }

    public function test_unverified_document_deletion_uses_standard_response(): void
    {
        Storage::fake('public');

        $guardian = $this->createUser('guardian');
        $student = $this->createUser('student');
        $guardian->students()->attach($student->id, [
            'relationship' => 'mother',
            'is_primary' => true,
        ]);

        $response = $this->actingAs($guardian)
            ->post("/api/v1/students/{$student->id}/documents", [
                'file' => UploadedFile::fake()->create('informe.pdf', 100, 'application/pdf'),
                'type' => 'medical_report',
            ], ['Accept' => 'application/json'])
            ->assertCreated();

        $documentId = $response->json('data.id');

        $this->actingAs($guardian)
            ->deleteJson("/api/v1/students/{$student->id}/documents/{$documentId}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', null)
            ->assertJsonPath('message', 'Documento estudiantil eliminado exitosamente');
    }
}
