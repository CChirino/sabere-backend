<?php

namespace Tests\Feature\Api\V1;

use App\Models\AcademicPeriod;
use App\Models\Grade;
use App\Models\Section;
use App\Models\StudentApplication;
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
            ->assertCreated();

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
            ->assertCreated();

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
            ->assertOk();

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
            ->assertJsonCount(1);
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
            ->assertOk();

        $this->assertDatabaseHas('student_profiles', [
            'user_id' => $student->id,
            'emergency_contact_name' => 'María Pérez',
            'allergies' => 'Polen',
        ]);
    }
}
