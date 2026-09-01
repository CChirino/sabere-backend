<?php

namespace Tests\Feature\Api\V1\Academic;

use App\Models\EvaluationItem;
use App\Models\EvaluationPlan;
use App\Models\SubjectAssignment;
use App\Models\Term;
use Tests\TestCase;

class EvaluationPlanTest extends TestCase
{
    public function test_teacher_can_create_evaluation_plan_with_items(): void
    {
        $teacher = $this->createUser('teacher');
        $assignment = SubjectAssignment::factory()->create(['teacher_id' => $teacher->id]);

        $term = Term::factory()->create([
            'academic_period_id' => $assignment->academic_period_id,
        ]);

        $response = $this->actingAs($teacher)
            ->postJson('/api/v1/evaluation-plans', [
                'academic_period_id' => $assignment->academic_period_id,
                'term_id' => $term->id,
                'subject_id' => $assignment->subject_id,
                'grade_id' => $assignment->section->grade_id,
                'section_id' => $assignment->section_id,
                'items' => [
                    ['name' => 'Examen I', 'type' => 'exam', 'evaluation_mode' => 'quantitative', 'weight' => 40],
                    ['name' => 'Tarea I', 'type' => 'homework', 'evaluation_mode' => 'quantitative', 'weight' => 60],
                ],
            ]);

        $response->assertCreated();
        $this->assertDatabaseCount('evaluation_plans', 1);
        $this->assertDatabaseCount('evaluation_items', 2);
    }

    public function test_plan_creation_fails_if_weights_do_not_sum_100(): void
    {
        $teacher = $this->createUser('teacher');
        $assignment = SubjectAssignment::factory()->create(['teacher_id' => $teacher->id]);

        $response = $this->actingAs($teacher)
            ->postJson('/api/v1/evaluation-plans', [
                'academic_period_id' => $assignment->academic_period_id,
                'term_id' => Term::factory()->create(['academic_period_id' => $assignment->academic_period_id])->id,
                'subject_id' => $assignment->subject_id,
                'grade_id' => $assignment->section->grade_id,
                'section_id' => $assignment->section_id,
                'items' => [
                    ['name' => 'Examen I', 'type' => 'exam', 'evaluation_mode' => 'quantitative', 'weight' => 30],
                    ['name' => 'Tarea I', 'type' => 'homework', 'evaluation_mode' => 'quantitative', 'weight' => 50],
                ],
            ]);

        $response->assertUnprocessable();
        $this->assertDatabaseCount('evaluation_plans', 0);
    }

    public function test_coordinator_can_approve_and_teacher_can_load_scores(): void
    {
        $teacher = $this->createUser('teacher');
        $coordinator = $this->createUser('coordinator');
        $assignment = SubjectAssignment::factory()->create(['teacher_id' => $teacher->id]);

        $plan = EvaluationPlan::factory()->forSection($assignment->section)->create([
            'academic_period_id' => $assignment->academic_period_id,
            'term_id' => Term::factory()->create(['academic_period_id' => $assignment->academic_period_id])->id,
            'subject_id' => $assignment->subject_id,
            'grade_id' => $assignment->section->grade_id,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        EvaluationItem::factory()->count(2)->create([
            'evaluation_plan_id' => $plan->id,
            'evaluation_mode' => 'quantitative',
            'weight' => 50,
        ]);

        $this->actingAs($coordinator)
            ->postJson("/api/v1/evaluation-plans/{$plan->id}/approve")
            ->assertOk();

        $student = $this->createUser('student');
        $assignment->section->enrollments()->create([
            'student_id' => $student->id,
            'academic_period_id' => $plan->academic_period_id,
            'enrollment_date' => now(),
            'status' => 'active',
        ]);

        foreach ($plan->items as $item) {
            $this->actingAs($teacher)
                ->postJson('/api/v1/student-evaluation-scores', [
                    'student_id' => $student->id,
                    'subject_assignment_id' => $assignment->id,
                    'evaluation_item_id' => $item->id,
                    'score' => 15,
                ])
                ->assertCreated();
        }

        $this->assertDatabaseHas('student_scores', [
            'student_id' => $student->id,
            'subject_assignment_id' => $assignment->id,
            'term_id' => $plan->term_id,
            'score' => 15,
        ]);
    }

    public function test_scores_cannot_be_loaded_before_plan_is_approved(): void
    {
        $teacher = $this->createUser('teacher');
        $plan = EvaluationPlan::factory()->create(['status' => 'draft']);
        $item = EvaluationItem::factory()->create([
            'evaluation_plan_id' => $plan->id,
            'evaluation_mode' => 'quantitative',
        ]);
        $assignment = SubjectAssignment::factory()->create(['teacher_id' => $teacher->id]);

        $student = $this->createUser('student');

        $response = $this->actingAs($teacher)
            ->postJson('/api/v1/student-evaluation-scores', [
                'student_id' => $student->id,
                'subject_assignment_id' => $assignment->id,
                'evaluation_item_id' => $item->id,
                'score' => 15,
            ]);

        $response->assertForbidden();
    }

    public function test_qualitative_grades_calculate_student_score(): void
    {
        $teacher = $this->createUser('teacher');
        $coordinator = $this->createUser('coordinator');
        $assignment = SubjectAssignment::factory()->create(['teacher_id' => $teacher->id]);

        $plan = EvaluationPlan::factory()->forSection($assignment->section)->create([
            'academic_period_id' => $assignment->academic_period_id,
            'term_id' => Term::factory()->create(['academic_period_id' => $assignment->academic_period_id])->id,
            'subject_id' => $assignment->subject_id,
            'grade_id' => $assignment->section->grade_id,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        EvaluationItem::factory()->create([
            'evaluation_plan_id' => $plan->id,
            'evaluation_mode' => 'qualitative',
            'weight' => 50,
        ]);

        EvaluationItem::factory()->create([
            'evaluation_plan_id' => $plan->id,
            'evaluation_mode' => 'qualitative',
            'weight' => 50,
        ]);

        $this->actingAs($coordinator)
            ->postJson("/api/v1/evaluation-plans/{$plan->id}/approve")
            ->assertOk();

        $student = $this->createUser('student');
        $assignment->section->enrollments()->create([
            'student_id' => $student->id,
            'academic_period_id' => $plan->academic_period_id,
            'enrollment_date' => now(),
            'status' => 'active',
        ]);

        $scores = ['A', 'B'];
        foreach ($plan->items as $index => $item) {
            $this->actingAs($teacher)
                ->postJson('/api/v1/student-evaluation-scores', [
                    'student_id' => $student->id,
                    'subject_assignment_id' => $assignment->id,
                    'evaluation_item_id' => $item->id,
                    'letter_grade' => $scores[$index],
                ])
                ->assertCreated();
        }

        $expected = (19 * 0.5) + (16 * 0.5); // A=19, B=16

        $this->assertDatabaseHas('student_scores', [
            'student_id' => $student->id,
            'subject_assignment_id' => $assignment->id,
            'term_id' => $plan->term_id,
            'score' => round($expected, 2),
        ]);
    }

    public function test_other_teacher_cannot_approve_plan(): void
    {
        $teacher = $this->createUser('teacher');
        $otherTeacher = $this->createUser('teacher');
        $plan = EvaluationPlan::factory()->create(['status' => 'submitted', 'submitted_at' => now()]);

        $this->actingAs($otherTeacher)
            ->postJson("/api/v1/evaluation-plans/{$plan->id}/approve")
            ->assertForbidden();
    }
}
