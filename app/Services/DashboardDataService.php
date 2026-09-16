<?php

namespace App\Services;

use App\Models\AcademicPeriod;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\StudentScore;
use App\Models\SubjectAssignment;
use App\Models\Task;
use App\Models\TaskSubmission;
use App\Models\Term;
use App\Models\User;

class DashboardDataService
{
    public function forUser(User $user): array
    {
        return match (true) {
            $user->hasRole('admin') => $this->admin(),
            $user->hasRole('director') => $this->director(),
            $user->hasRole('coordinator') => $this->coordinator(),
            $user->hasRole('teacher') => $this->teacher($user),
            $user->hasRole('student') => $this->student($user),
            $user->hasRole('guardian') => $this->guardian($user),
            default => ['message' => 'Rol no reconocido'],
        };
    }

    private function admin(): array
    {
        $currentPeriod = $this->currentPeriod();

        return [
            'current_period' => $currentPeriod,
            'stats' => [
                'total_users' => User::count(),
                'total_students' => User::role('student')->count(),
                'total_teachers' => User::role('teacher')->count(),
                'total_guardians' => User::role('guardian')->count(),
                'total_sections' => $currentPeriod ? Section::where('academic_period_id', $currentPeriod->id)->count() : 0,
                'total_enrollments' => $currentPeriod ? Enrollment::where('academic_period_id', $currentPeriod->id)->where('status', 'active')->count() : 0,
            ],
            'recent_users' => User::latest()->limit(5)->get(),
        ];
    }

    private function director(): array
    {
        $currentPeriod = $this->currentPeriod();
        $currentTerm = $this->currentTerm($currentPeriod);

        return [
            'current_period' => $currentPeriod,
            'current_term' => $currentTerm,
            'stats' => [
                'total_students' => User::role('student')->count(),
                'total_teachers' => User::role('teacher')->count(),
                'active_enrollments' => $currentPeriod ? Enrollment::where('academic_period_id', $currentPeriod->id)->where('status', 'active')->count() : 0,
                'total_sections' => $currentPeriod ? Section::where('academic_period_id', $currentPeriod->id)->count() : 0,
                'total_subject_assignments' => $currentPeriod ? SubjectAssignment::where('academic_period_id', $currentPeriod->id)->count() : 0,
            ],
            'enrollments_by_level' => $currentPeriod ? $this->enrollmentsByLevel($currentPeriod->id) : [],
            'pending_tasks' => Task::where('is_published', true)
                ->whereDate('due_date', '>=', now())
                ->count(),
        ];
    }

    private function coordinator(): array
    {
        $currentPeriod = $this->currentPeriod();
        $currentTerm = $this->currentTerm($currentPeriod);

        return [
            'current_period' => $currentPeriod,
            'current_term' => $currentTerm,
            'stats' => [
                'total_teachers' => User::role('teacher')->count(),
                'total_students' => $currentPeriod ? Enrollment::where('academic_period_id', $currentPeriod->id)->where('status', 'active')->count() : 0,
                'total_sections' => $currentPeriod ? Section::where('academic_period_id', $currentPeriod->id)->count() : 0,
                'pending_grades' => $currentTerm ? $this->pendingGradesCount($currentTerm->id) : 0,
            ],
            'sections' => $currentPeriod ? Section::with(['grade.educationLevel'])
                ->where('academic_period_id', $currentPeriod->id)
                ->withCount(['enrollments' => function ($q) {
                    $q->where('status', 'active');
                }])
                ->get() : [],
        ];
    }

    private function teacher(User $user): array
    {
        $teacherId = $user->id;
        $currentPeriod = $this->currentPeriod();
        $currentTerm = $this->currentTerm($currentPeriod);

        $assignments = SubjectAssignment::with(['subject', 'section.grade.educationLevel'])
            ->where('teacher_id', $teacherId)
            ->where('status', true)
            ->when($currentPeriod, function ($q) use ($currentPeriod) {
                $q->where('academic_period_id', $currentPeriod->id);
            })
            ->get();

        $pendingSubmissions = TaskSubmission::whereHas('task.subjectAssignment', function ($q) use ($teacherId) {
            $q->where('teacher_id', $teacherId);
        })
            ->whereIn('status', ['submitted', 'late'])
            ->count();

        $upcomingTasks = Task::whereHas('subjectAssignment', function ($q) use ($teacherId) {
            $q->where('teacher_id', $teacherId);
        })
            ->where('is_published', true)
            ->whereDate('due_date', '>=', now())
            ->whereDate('due_date', '<=', now()->addDays(7))
            ->with(['subjectAssignment.subject', 'subjectAssignment.section.grade'])
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        return [
            'current_period' => $currentPeriod,
            'current_term' => $currentTerm,
            'assignments' => $assignments,
            'stats' => [
                'total_assignments' => $assignments->count(),
                'total_students' => $assignments->sum(function ($a) {
                    return $a->section->enrollments()->where('status', 'active')->count();
                }),
                'pending_submissions' => $pendingSubmissions,
            ],
            'upcoming_tasks' => $upcomingTasks,
        ];
    }

    private function student(User $user): array
    {
        $studentId = $user->id;

        $enrollment = Enrollment::with(['section.grade.educationLevel', 'academicPeriod'])
            ->where('student_id', $studentId)
            ->where('status', 'active')
            ->first();

        if (! $enrollment) {
            return [
                'message' => 'No tienes una inscripción activa',
                'enrollment' => null,
            ];
        }

        $currentTerm = Term::where('academic_period_id', $enrollment->academic_period_id)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first();

        $pendingTasks = Task::whereHas('subjectAssignment', function ($q) use ($enrollment) {
            $q->where('section_id', $enrollment->section_id);
        })
            ->where('is_published', true)
            ->whereDoesntHave('submissions', function ($q) use ($studentId) {
                $q->where('student_id', $studentId)
                    ->whereIn('status', ['submitted', 'graded']);
            })
            ->with(['subjectAssignment.subject'])
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        $gradedSubmissions = TaskSubmission::with(['task.subjectAssignment.subject', 'task.term'])
            ->where('student_id', $studentId)
            ->where('status', 'graded')
            ->whereHas('task.subjectAssignment', function ($q) use ($enrollment) {
                $q->where('section_id', $enrollment->section_id);
            })
            ->get();

        $currentScores = $gradedSubmissions->map(function ($submission) {
            return [
                'id' => $submission->id,
                'title' => $submission->task->title,
                'type' => $submission->task->type,
                'score' => $submission->score,
                'max_score' => $submission->task->max_score,
                'feedback' => $submission->feedback,
                'graded_at' => $submission->graded_at,
                'subject_assignment' => $submission->task->subjectAssignment,
            ];
        });

        $totalScore = $gradedSubmissions->sum('score');
        $totalMaxScore = $gradedSubmissions->sum(function ($sub) {
            return $sub->task->max_score;
        });
        $average = $totalMaxScore > 0 ? round(($totalScore / $totalMaxScore) * 20, 2) : null;

        $subjects = SubjectAssignment::with(['subject', 'teacher'])
            ->where('section_id', $enrollment->section_id)
            ->where('status', true)
            ->get();

        return [
            'enrollment' => $enrollment,
            'current_term' => $currentTerm,
            'stats' => [
                'pending_tasks' => $pendingTasks->count(),
                'current_average' => $average ? round($average, 2) : null,
                'total_subjects' => $subjects->count(),
            ],
            'pending_tasks' => $pendingTasks,
            'current_scores' => $currentScores,
            'subjects' => $subjects,
        ];
    }

    private function guardian(User $user): array
    {
        $students = $user->students()
            ->with(['enrollments' => function ($q) {
                $q->where('status', 'active')
                    ->with(['section.grade.educationLevel', 'academicPeriod']);
            }])
            ->get();

        $studentsData = $students->map(function ($student) {
            $enrollment = $student->enrollments->first();

            if (! $enrollment) {
                return [
                    'student' => $student,
                    'enrollment' => null,
                    'pending_tasks' => 0,
                    'current_average' => null,
                ];
            }

            $currentTerm = Term::where('academic_period_id', $enrollment->academic_period_id)
                ->whereDate('start_date', '<=', now())
                ->whereDate('end_date', '>=', now())
                ->first();

            $pendingTasks = Task::whereHas('subjectAssignment', function ($q) use ($enrollment) {
                $q->where('section_id', $enrollment->section_id);
            })
                ->where('is_published', true)
                ->whereDoesntHave('submissions', function ($q) use ($student) {
                    $q->where('student_id', $student->id)
                        ->whereIn('status', ['submitted', 'graded']);
                })
                ->count();

            $average = $currentTerm ? StudentScore::where('student_id', $student->id)
                ->where('term_id', $currentTerm->id)
                ->avg('score') : null;

            return [
                'student' => $student,
                'enrollment' => $enrollment,
                'pending_tasks' => $pendingTasks,
                'current_average' => $average ? round($average, 2) : null,
            ];
        });

        return [
            'students' => $studentsData,
            'total_students' => $students->count(),
        ];
    }

    private function currentPeriod(): ?AcademicPeriod
    {
        return AcademicPeriod::where('status', true)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first();
    }

    private function currentTerm(?AcademicPeriod $period): ?Term
    {
        if (! $period) {
            return null;
        }

        return Term::where('academic_period_id', $period->id)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first();
    }

    private function enrollmentsByLevel(int $periodId): array
    {
        return Enrollment::where('academic_period_id', $periodId)
            ->where('status', 'active')
            ->with('section.grade.educationLevel')
            ->get()
            ->groupBy('section.grade.educationLevel.name')
            ->map(function ($enrollments) {
                return $enrollments->count();
            })
            ->toArray();
    }

    private function pendingGradesCount(int $termId): int
    {
        $term = Term::find($termId);
        if (! $term) {
            return 0;
        }

        $totalExpected = 0;
        $totalGraded = 0;

        $assignments = SubjectAssignment::where('academic_period_id', $term->academic_period_id)
            ->where('status', true)
            ->with('section.enrollments')
            ->get();

        foreach ($assignments as $assignment) {
            $studentCount = $assignment->section->enrollments()->where('status', 'active')->count();
            $totalExpected += $studentCount;

            $gradedCount = StudentScore::where('subject_assignment_id', $assignment->id)
                ->where('term_id', $termId)
                ->count();
            $totalGraded += $gradedCount;
        }

        return $totalExpected - $totalGraded;
    }
}
