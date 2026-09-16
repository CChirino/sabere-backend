<?php

namespace Tests\Feature\Console;

use App\Models\AcademicPeriod;
use App\Models\Grade;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use Tests\TestCase;

class SchoolStartYearTest extends TestCase
{
    public function test_start_year_creates_period_with_structure(): void
    {
        // Crear período fuente con estructura completa
        $source = AcademicPeriod::factory()->create([
            'code' => '2024-2025',
            'school_year' => '2024-2025',
            'start_date' => '2024-09-16',
            'end_date' => '2025-07-15',
            'status' => true,
        ]);

        $grade = Grade::factory()->create();
        $teacher = $this->createUser('teacher');
        $subject = Subject::factory()->create();

        $section = Section::factory()->create([
            'academic_period_id' => $source->id,
            'grade_id' => $grade->id,
            'name' => 'A',
        ]);

        $assignment = SubjectAssignment::factory()->create([
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'academic_period_id' => $source->id,
        ]);

        Schedule::factory()->create([
            'subject_assignment_id' => $assignment->id,
            'day_of_week' => 'monday',
            'start_time' => '08:00',
            'end_time' => '09:30',
            'classroom' => 'Aula 1',
        ]);

        // Ejecutar comando
        $this->artisan('school:start-year', [
            'school_year' => '2025-2026',
            '--from' => $source->id,
            '--no-interaction' => true,
        ])->assertSuccessful();

        // Verificar período creado
        $this->assertDatabaseHas('academic_periods', [
            'code' => '2025-2026',
            'school_year' => '2025-2026',
            'status' => true,
        ]);

        $newPeriod = AcademicPeriod::where('code', '2025-2026')->first();

        // Verificar 3 lapsos
        $this->assertEquals(3, $newPeriod->terms()->count());

        // Verificar sección copiada
        $this->assertDatabaseHas('sections', [
            'academic_period_id' => $newPeriod->id,
            'grade_id' => $grade->id,
            'name' => 'A',
        ]);

        // Verificar asignación copiada
        $this->assertDatabaseHas('subject_assignments', [
            'academic_period_id' => $newPeriod->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
        ]);

        // Verificar horario copiado
        $newSection = Section::where('academic_period_id', $newPeriod->id)->first();
        $newAssignment = SubjectAssignment::where('section_id', $newSection->id)
            ->where('academic_period_id', $newPeriod->id)
            ->first();

        $this->assertDatabaseHas('schedules', [
            'subject_assignment_id' => $newAssignment->id,
            'day_of_week' => 'monday',
            'classroom' => 'Aula 1',
        ]);
    }

    public function test_start_year_fails_if_year_already_exists(): void
    {
        AcademicPeriod::factory()->create([
            'code' => '2025-2026',
            'school_year' => '2025-2026',
        ]);

        $this->artisan('school:start-year', [
            'school_year' => '2025-2026',
            '--no-interaction' => true,
        ])->assertFailed();
    }

    public function test_start_year_fails_with_invalid_format(): void
    {
        $this->artisan('school:start-year', [
            'school_year' => '2025',
            '--no-interaction' => true,
        ])->assertFailed();
    }

    public function test_start_year_does_not_copy_enrollments(): void
    {
        $source = AcademicPeriod::factory()->create([
            'code' => '2024-2025',
            'school_year' => '2024-2025',
            'status' => true,
        ]);

        $grade = Grade::factory()->create();
        Section::factory()->create([
            'academic_period_id' => $source->id,
            'grade_id' => $grade->id,
        ]);

        $this->artisan('school:start-year', [
            'school_year' => '2025-2026',
            '--from' => $source->id,
            '--no-interaction' => true,
        ])->assertSuccessful();

        $newPeriod = AcademicPeriod::where('code', '2025-2026')->first();

        // No se deben copiar matrículas
        $this->assertEquals(0, $newPeriod->enrollments()->count());
    }
}
