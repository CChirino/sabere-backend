<?php

namespace Tests\Feature\Api\V1\Academic;

use App\Models\Enrollment;
use App\Models\StudentGuardian;
use App\Models\StudentScore;
use App\Models\SubjectAssignment;
use App\Models\Task;
use App\Models\TaskSubmission;
use App\Models\Term;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    public function test_student_can_view_own_scores_but_not_other_students_scores(): void
    {
        $student = $this->createUser('student');
        $otherStudent = $this->createUser('student');

        $score = StudentScore::factory()->create(['student_id' => $otherStudent->id]);

        $response = $this->actingAs($student)
            ->getJson("/api/v1/student-scores/{$score->id}");

        $response->assertForbidden();
    }

    public function test_student_can_view_own_score(): void
    {
        $student = $this->createUser('student');
        $score = StudentScore::factory()->create(['student_id' => $student->id]);

        $response = $this->actingAs($student)
            ->getJson("/api/v1/student-scores/{$score->id}");

        $response->assertOk();
    }

    public function test_guardian_can_view_linked_student_scores_but_not_other_students(): void
    {
        $guardian = $this->createUser('guardian');
        $linkedStudent = $this->createUser('student');
        $otherStudent = $this->createUser('student');

        StudentGuardian::factory()->create([
            'guardian_id' => $guardian->id,
            'student_id' => $linkedStudent->id,
        ]);

        $otherScore = StudentScore::factory()->create(['student_id' => $otherStudent->id]);

        $response = $this->actingAs($guardian)
            ->getJson("/api/v1/student-scores/{$otherScore->id}");

        $response->assertForbidden();
    }

    public function test_teacher_cannot_update_score_from_other_teacher_assignment(): void
    {
        $teacher = $this->createUser('teacher');
        $otherTeacher = $this->createUser('teacher');

        $assignment = SubjectAssignment::factory()->create(['teacher_id' => $otherTeacher->id]);
        $score = StudentScore::factory()->create([
            'subject_assignment_id' => $assignment->id,
        ]);

        $response = $this->actingAs($teacher)
            ->putJson("/api/v1/student-scores/{$score->id}", [
                'score' => 18,
            ]);

        $response->assertForbidden();
    }

    public function test_teacher_can_update_own_assignment_score(): void
    {
        $teacher = $this->createUser('teacher');
        $assignment = SubjectAssignment::factory()->create(['teacher_id' => $teacher->id]);
        $score = StudentScore::factory()->create([
            'subject_assignment_id' => $assignment->id,
        ]);

        $response = $this->actingAs($teacher)
            ->putJson("/api/v1/student-scores/{$score->id}", [
                'score' => 18,
            ]);

        $response->assertOk();
    }

    public function test_student_cannot_create_score(): void
    {
        $student = $this->createUser('student');
        $assignment = SubjectAssignment::factory()->create();
        $term = Term::factory()->create();

        $response = $this->actingAs($student)
            ->postJson('/api/v1/student-scores', [
                'student_id' => $student->id,
                'subject_assignment_id' => $assignment->id,
                'term_id' => $term->id,
                'score' => 18,
            ]);

        $response->assertForbidden();
    }

    public function test_student_cannot_view_other_student_enrollment(): void
    {
        $student = $this->createUser('student');
        $otherStudent = $this->createUser('student');
        $enrollment = Enrollment::factory()->create(['student_id' => $otherStudent->id]);

        $response = $this->actingAs($student)
            ->getJson("/api/v1/enrollments/{$enrollment->id}");

        $response->assertForbidden();
    }

    public function test_teacher_cannot_update_other_teacher_task(): void
    {
        $teacher = $this->createUser('teacher');
        $otherTeacher = $this->createUser('teacher');
        $assignment = SubjectAssignment::factory()->create(['teacher_id' => $otherTeacher->id]);
        $task = Task::factory()->create(['subject_assignment_id' => $assignment->id]);

        $response = $this->actingAs($teacher)
            ->putJson("/api/v1/tasks/{$task->id}", [
                'title' => 'Tarea modificada',
            ]);

        $response->assertForbidden();
    }

    public function test_teacher_can_update_own_task(): void
    {
        $teacher = $this->createUser('teacher');
        $assignment = SubjectAssignment::factory()->create(['teacher_id' => $teacher->id]);
        $task = Task::factory()->create(['subject_assignment_id' => $assignment->id]);

        $response = $this->actingAs($teacher)
            ->putJson("/api/v1/tasks/{$task->id}", [
                'title' => 'Tarea modificada',
            ]);

        $response->assertOk();
    }

    public function test_student_cannot_grade_submission(): void
    {
        $student = $this->createUser('student');
        $submission = TaskSubmission::factory()->create();

        $response = $this->actingAs($student)
            ->postJson("/api/v1/task-submissions/{$submission->id}/grade", [
                'score' => 10,
            ]);

        $response->assertForbidden();
    }

    public function test_admin_can_view_any_score(): void
    {
        $admin = $this->createUser('admin');
        $score = StudentScore::factory()->create();

        $response = $this->actingAs($admin)
            ->getJson("/api/v1/student-scores/{$score->id}");

        $response->assertOk();
    }

    public function test_mass_assignment_is_not_possible_in_score_update(): void
    {
        $teacher = $this->createUser('teacher');
        $assignment = SubjectAssignment::factory()->create(['teacher_id' => $teacher->id]);
        $score = StudentScore::factory()->create([
            'subject_assignment_id' => $assignment->id,
        ]);

        $otherAssignment = SubjectAssignment::factory()->create();

        $this->actingAs($teacher)
            ->putJson("/api/v1/student-scores/{$score->id}", [
                'score' => 18,
                'subject_assignment_id' => $otherAssignment->id,
            ]);

        $score->refresh();
        $this->assertEquals($assignment->id, $score->subject_assignment_id);
    }
}
