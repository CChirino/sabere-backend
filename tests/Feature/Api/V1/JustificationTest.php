<?php

namespace Tests\Feature\Api\V1;

use App\Models\AcademicPeriod;
use App\Models\Attendance;
use App\Models\Justification;
use App\Models\Section;
use App\Models\StudentGuardian;
use Tests\TestCase;

class JustificationTest extends TestCase
{
    public function test_guardian_can_create_justification(): void
    {
        $guardian = $this->createUser('guardian');
        $student = $this->createUser('student');
        StudentGuardian::factory()->create([
            'guardian_id' => $guardian->id,
            'student_id' => $student->id,
        ]);
        $academicPeriod = AcademicPeriod::factory()->create();

        $this->actingAs($guardian)
            ->postJson(route('api.justifications.store'), [
                'student_id' => $student->id,
                'academic_period_id' => $academicPeriod->id,
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d'),
                'reason' => 'Cita médica',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Justificativo creado exitosamente')
            ->assertJsonPath('data.student_id', $student->id);

        $this->assertDatabaseHas('justifications', [
            'guardian_id' => $guardian->id,
            'student_id' => $student->id,
            'status' => 'pending',
        ]);
    }

    public function test_staff_can_approve_and_excuse_attendance(): void
    {
        $coordinator = $this->createUser('coordinator');
        $guardian = $this->createUser('guardian');
        $student = $this->createUser('student');
        StudentGuardian::factory()->create([
            'guardian_id' => $guardian->id,
            'student_id' => $student->id,
        ]);
        $academicPeriod = AcademicPeriod::factory()->create();
        $section = Section::factory()->create();
        $date = now()->format('Y-m-d');

        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'section_id' => $section->id,
            'academic_period_id' => $academicPeriod->id,
            'date' => $date,
            'status' => Attendance::STATUS_ABSENT,
        ]);

        $justification = Justification::factory()->create([
            'guardian_id' => $guardian->id,
            'student_id' => $student->id,
            'academic_period_id' => $academicPeriod->id,
            'start_date' => $date,
            'end_date' => $date,
            'status' => 'pending',
        ]);

        $this->actingAs($coordinator)
            ->postJson(route('api.justifications.approve', $justification), [
                'notes' => 'Constancia recibida',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Justificativo aprobado exitosamente')
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('justifications', [
            'id' => $justification->id,
            'status' => 'approved',
        ]);

        $this->assertEquals(Attendance::STATUS_EXCUSED, $attendance->fresh()->status);
    }

    public function test_guardian_cannot_approve_justification(): void
    {
        $guardian = $this->createUser('guardian');
        $student = $this->createUser('student');
        StudentGuardian::factory()->create([
            'guardian_id' => $guardian->id,
            'student_id' => $student->id,
        ]);
        $academicPeriod = AcademicPeriod::factory()->create();
        $justification = Justification::factory()->create([
            'guardian_id' => $guardian->id,
            'student_id' => $student->id,
            'academic_period_id' => $academicPeriod->id,
        ]);

        $this->actingAs($guardian)
            ->postJson(route('api.justifications.approve', $justification))
            ->assertForbidden();
    }

    public function test_staff_can_reject_justification(): void
    {
        $director = $this->createUser('director');
        $guardian = $this->createUser('guardian');
        $student = $this->createUser('student');
        StudentGuardian::factory()->create([
            'guardian_id' => $guardian->id,
            'student_id' => $student->id,
        ]);
        $academicPeriod = AcademicPeriod::factory()->create();
        $justification = Justification::factory()->create([
            'guardian_id' => $guardian->id,
            'student_id' => $student->id,
            'academic_period_id' => $academicPeriod->id,
        ]);

        $this->actingAs($director)
            ->postJson(route('api.justifications.reject', $justification), [
                'notes' => 'Falta documento',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Justificativo rechazado exitosamente')
            ->assertJsonPath('data.status', 'rejected');

        $this->assertDatabaseHas('justifications', [
            'id' => $justification->id,
            'status' => 'rejected',
        ]);
    }

    public function test_justification_list_and_show_use_standard_responses(): void
    {
        $coordinator = $this->createUser('coordinator');
        $justification = Justification::factory()->create();

        $this->actingAs($coordinator)
            ->getJson(route('api.justifications.index', ['per_page' => 5]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Justificativos obtenidos exitosamente')
            ->assertJsonPath('per_page', 5)
            ->assertJsonStructure(['data', 'current_page', 'last_page', 'total']);

        $this->actingAs($coordinator)
            ->getJson(route('api.justifications.show', $justification))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Justificativo obtenido exitosamente')
            ->assertJsonPath('data.id', $justification->id);
    }

    public function test_staff_can_delete_justification_with_standard_response(): void
    {
        $coordinator = $this->createUser('coordinator');
        $justification = Justification::factory()->create();

        $this->actingAs($coordinator)
            ->deleteJson(route('api.justifications.destroy', $justification))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', null)
            ->assertJsonPath('message', 'Justificativo eliminado exitosamente');
    }
}
