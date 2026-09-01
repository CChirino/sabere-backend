<?php

namespace Tests\Feature\Api\V1\Academic;

use App\Models\AcademicPeriod;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\StudentScore;
use App\Models\Term;
use Tests\TestCase;

class AcademicClosingTest extends TestCase
{
    public function test_term_closing_marks_scores_as_final(): void
    {
        $coordinator = $this->createUser('coordinator');
        $term = Term::factory()->create();

        $score = StudentScore::factory()->create([
            'term_id' => $term->id,
            'is_final' => false,
        ]);

        $this->actingAs($coordinator)
            ->postJson("/api/v1/terms/{$term->id}/close")
            ->assertOk();

        $this->assertDatabaseHas('terms', [
            'id' => $term->id,
            'is_closed' => true,
        ]);

        $this->assertDatabaseHas('student_scores', [
            'id' => $score->id,
            'is_final' => true,
        ]);
    }

    public function test_academic_period_closing_calculates_annual_scores_and_promotion(): void
    {
        $coordinator = $this->createUser('coordinator');
        $period = AcademicPeriod::factory()->create();

        $term1 = Term::factory()->create([
            'academic_period_id' => $period->id,
            'weight' => 40,
        ]);

        $term2 = Term::factory()->create([
            'academic_period_id' => $period->id,
            'weight' => 60,
        ]);

        $section = Section::factory()->create();
        $student = $this->createUser('student');

        Enrollment::factory()->create([
            'student_id' => $student->id,
            'section_id' => $section->id,
            'academic_period_id' => $period->id,
            'status' => 'active',
        ]);

        $assignment = \App\Models\SubjectAssignment::factory()->create();

        StudentScore::factory()->create([
            'student_id' => $student->id,
            'subject_assignment_id' => $assignment->id,
            'term_id' => $term1->id,
            'score' => 15,
            'is_final' => true,
        ]);

        StudentScore::factory()->create([
            'student_id' => $student->id,
            'subject_assignment_id' => $assignment->id,
            'term_id' => $term2->id,
            'score' => 17,
            'is_final' => true,
        ]);

        $this->actingAs($coordinator)
            ->postJson("/api/v1/academic-periods/{$period->id}/close")
            ->assertOk();

        $expected = round((15 * 0.4) + (17 * 0.6), 2);

        $this->assertDatabaseHas('annual_subject_scores', [
            'student_id' => $student->id,
            'academic_period_id' => $period->id,
            'final_score' => $expected,
        ]);

        $this->assertDatabaseHas('annual_averages', [
            'student_id' => $student->id,
            'academic_period_id' => $period->id,
            'average_score' => $expected,
        ]);

        $this->assertDatabaseHas('student_promotions', [
            'student_id' => $student->id,
            'academic_period_id' => $period->id,
            'status' => 'promoted',
        ]);
    }

    public function test_failed_subject_creates_recovery_registration_and_repeating_status(): void
    {
        $coordinator = $this->createUser('coordinator');
        $period = AcademicPeriod::factory()->create();

        $term = Term::create([
            'academic_period_id' => $period->id,
            'name' => 'Primer Lapso',
            'number' => 1,
            'start_date' => now()->subMonths(3),
            'end_date' => now(),
            'weight' => 100,
            'status' => true,
        ]);

        $section = Section::factory()->create();
        $student = $this->createUser('student');

        Enrollment::factory()->create([
            'student_id' => $student->id,
            'section_id' => $section->id,
            'academic_period_id' => $period->id,
            'status' => 'active',
        ]);

        StudentScore::factory()->create([
            'student_id' => $student->id,
            'term_id' => $term->id,
            'score' => 8,
            'is_final' => true,
        ]);

        $this->actingAs($coordinator)
            ->postJson("/api/v1/academic-periods/{$period->id}/close")
            ->assertOk();

        $this->assertDatabaseHas('recovery_registrations', [
            'student_id' => $student->id,
            'academic_period_id' => $period->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('annual_averages', [
            'student_id' => $student->id,
            'academic_period_id' => $period->id,
            'status' => 'repeating',
        ]);
    }

    public function test_recovery_registration_can_be_graded(): void
    {
        $coordinator = $this->createUser('coordinator');
        $registration = \App\Models\RecoveryRegistration::factory()->create();

        $this->actingAs($coordinator)
            ->postJson("/api/v1/recovery-registrations/{$registration->id}/grade", [
                'recovery_score' => 12,
            ])
            ->assertOk();

        $this->assertDatabaseHas('recovery_registrations', [
            'id' => $registration->id,
            'recovery_score' => 12,
            'status' => 'passed',
        ]);
    }

    public function test_teacher_cannot_close_academic_period(): void
    {
        $teacher = $this->createUser('teacher');
        $period = AcademicPeriod::factory()->create();

        $this->actingAs($teacher)
            ->postJson("/api/v1/academic-periods/{$period->id}/close")
            ->assertForbidden();
    }
}
