<?php

namespace Tests\Feature\Api\V1;

use App\Models\AcademicPeriod;
use App\Models\DisciplinaryRecord;
use App\Models\IncidentType;
use App\Models\Section;
use App\Models\StudentGuardian;
use App\Models\SubjectAssignment;
use Tests\TestCase;

class DisciplineTest extends TestCase
{
    public function test_teacher_can_create_disciplinary_record(): void
    {
        $teacher = $this->createUser('teacher');
        $student = $this->createUser('student');
        $section = Section::factory()->create();
        $academicPeriod = AcademicPeriod::factory()->create();
        $incidentType = IncidentType::factory()->create();
        SubjectAssignment::factory()->create([
            'teacher_id' => $teacher->id,
            'section_id' => $section->id,
            'academic_period_id' => $academicPeriod->id,
        ]);

        $this->actingAs($teacher)
            ->postJson(route('api.disciplinary-records.store'), [
                'student_id' => $student->id,
                'section_id' => $section->id,
                'academic_period_id' => $academicPeriod->id,
                'incident_type_id' => $incidentType->id,
                'severity' => 'leve',
                'description' => 'Uso inadecuado del celular',
                'date' => now()->format('Y-m-d'),
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Incidencia creada exitosamente')
            ->assertJsonPath('data.student_id', $student->id);

        $this->assertDatabaseHas('disciplinary_records', [
            'student_id' => $student->id,
            'recorded_by' => $teacher->id,
            'severity' => 'leve',
        ]);
    }

    public function test_student_can_view_own_records(): void
    {
        $student = $this->createUser('student');
        $record = DisciplinaryRecord::factory()->create(['student_id' => $student->id]);

        $this->actingAs($student)
            ->getJson(route('api.disciplinary-records.show', $record))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Incidencia obtenida exitosamente')
            ->assertJsonPath('data.id', $record->id);
    }

    public function test_guardian_can_view_child_records(): void
    {
        $guardian = $this->createUser('guardian');
        $student = $this->createUser('student');
        StudentGuardian::factory()->create([
            'guardian_id' => $guardian->id,
            'student_id' => $student->id,
        ]);
        $record = DisciplinaryRecord::factory()->create(['student_id' => $student->id]);

        $this->actingAs($guardian)
            ->getJson(route('api.disciplinary-records.show', $record))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Incidencia obtenida exitosamente')
            ->assertJsonPath('data.id', $record->id);
    }

    public function test_guardian_cannot_view_other_records(): void
    {
        $guardian = $this->createUser('guardian');
        $record = DisciplinaryRecord::factory()->create();

        $this->actingAs($guardian)
            ->getJson(route('api.disciplinary-records.show', $record))
            ->assertForbidden();
    }

    public function test_private_records_are_hidden_from_students_and_guardians(): void
    {
        $student = $this->createUser('student');
        $record = DisciplinaryRecord::factory()->create([
            'student_id' => $student->id,
            'is_private' => true,
        ]);

        $this->actingAs($student)
            ->getJson(route('api.disciplinary-records.show', $record))
            ->assertForbidden();
    }

    public function test_discipline_lists_use_standard_responses(): void
    {
        $coordinator = $this->createUser('coordinator');
        IncidentType::factory()->create(['is_active' => true]);
        DisciplinaryRecord::factory()->create();

        $this->actingAs($coordinator)
            ->getJson(route('api.disciplinary-records.index', ['per_page' => 5]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Incidencias obtenidas exitosamente')
            ->assertJsonPath('per_page', 5)
            ->assertJsonStructure(['data', 'current_page', 'last_page', 'total']);

        $this->actingAs($coordinator)
            ->getJson(route('api.incident-types.index', ['per_page' => 5]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Tipos de incidencia obtenidos exitosamente')
            ->assertJsonPath('per_page', 5);
    }

    public function test_staff_can_update_and_delete_record_with_standard_responses(): void
    {
        $coordinator = $this->createUser('coordinator');
        $record = DisciplinaryRecord::factory()->create();

        $this->actingAs($coordinator)
            ->putJson(route('api.disciplinary-records.update', $record), [
                'description' => 'Descripción actualizada',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Incidencia actualizada exitosamente')
            ->assertJsonPath('data.description', 'Descripción actualizada');

        $this->actingAs($coordinator)
            ->deleteJson(route('api.disciplinary-records.destroy', $record))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', null)
            ->assertJsonPath('message', 'Incidencia eliminada exitosamente');
    }
}
