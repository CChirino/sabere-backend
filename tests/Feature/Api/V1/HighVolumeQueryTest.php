<?php

namespace Tests\Feature\Api\V1;

use App\Models\AcademicPeriod;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\StudentScore;
use App\Models\SubjectAssignment;
use App\Models\Term;
use App\Services\CoordinatorDataService;
use App\Services\DashboardDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HighVolumeQueryTest extends TestCase
{
    public function test_scores_overview_query_count_does_not_grow_with_assignments(): void
    {
        $term = Term::factory()->create();
        $assignment = SubjectAssignment::factory()->create();
        StudentScore::factory()->create([
            'subject_assignment_id' => $assignment->id,
            'term_id' => $term->id,
            'score' => 8,
        ]);

        $request = Request::create('/api/v1/coordinator/scores-overview', 'GET', [
            'term_id' => $term->id,
            'per_page' => 10,
        ]);

        $smallCount = $this->countQueries(fn () => app(CoordinatorDataService::class)->scoresOverview($request));

        SubjectAssignment::factory()->count(4)->create()->each(function ($additionalAssignment) use ($term) {
            StudentScore::factory()->create([
                'subject_assignment_id' => $additionalAssignment->id,
                'term_id' => $term->id,
                'score' => 15,
            ]);
        });

        $largeCount = $this->countQueries(fn () => app(CoordinatorDataService::class)->scoresOverview($request));

        $this->assertSame($smallCount, $largeCount);
    }

    public function test_teacher_detail_query_count_does_not_grow_with_assignments(): void
    {
        $teacher = $this->createUser('teacher');
        SubjectAssignment::factory()->create(['teacher_id' => $teacher->id]);

        $smallCount = $this->countQueries(fn () => app(CoordinatorDataService::class)->teacherShow($teacher->id));

        SubjectAssignment::factory()->count(4)->create(['teacher_id' => $teacher->id]);

        $largeCount = $this->countQueries(fn () => app(CoordinatorDataService::class)->teacherShow($teacher->id));

        $this->assertSame($smallCount, $largeCount);
    }

    public function test_guardian_dashboard_query_count_does_not_grow_with_students(): void
    {
        $guardian = $this->createUser('guardian');
        $period = AcademicPeriod::factory()->create([
            'status' => true,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonth(),
        ]);
        Term::factory()->create([
            'academic_period_id' => $period->id,
            'start_date' => now()->subWeek(),
            'end_date' => now()->addWeek(),
        ]);

        $this->attachStudentWithEnrollment($guardian->id, $period->id);
        app(DashboardDataService::class)->forUser($guardian);

        $smallCount = $this->countQueries(fn () => app(DashboardDataService::class)->forUser($guardian));

        foreach (range(1, 4) as $unused) {
            $this->attachStudentWithEnrollment($guardian->id, $period->id);
        }

        $guardian->unsetRelation('students');
        $largeCount = $this->countQueries(fn () => app(DashboardDataService::class)->forUser($guardian));

        $this->assertSame($smallCount, $largeCount);
    }

    private function attachStudentWithEnrollment(int $guardianId, int $periodId): void
    {
        $student = $this->createUser('student');
        $guardian = \App\Models\User::findOrFail($guardianId);
        $guardian->students()->attach($student->id, [
            'relationship' => 'guardian',
            'is_primary' => true,
        ]);
        $section = Section::factory()->create(['academic_period_id' => $periodId]);
        Enrollment::factory()->create([
            'student_id' => $student->id,
            'section_id' => $section->id,
            'academic_period_id' => $periodId,
            'status' => 'active',
        ]);
    }

    private function countQueries(callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $callback();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }
}
