<?php

namespace App\Services;

use App\Models\StudentScore;
use App\Models\SubjectAssignment;
use App\Models\Task;
use App\Models\TaskSubmission;
use App\Models\Term;
use App\Models\User;
use Illuminate\Http\Request;

class CoordinatorDataService
{
    public function teachers(Request $request): array
    {
        $query = User::role('teacher')
            ->withCount(['subjectAssignments as assignments_count' => function ($q) {
                $q->where('status', true);
            }])
            ->with(['subjectAssignments' => function ($q) {
                $q->where('status', true)
                    ->with(['subject:id,name', 'section:id,name']);
            }]);

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = app(PaginationService::class)->perPage($request);
        $teachers = $query->orderBy('name')->paginate($perPage);

        $teachers->getCollection()->transform(function ($teacher) {
            $subjects = $teacher->subjectAssignments->pluck('subject.name')->filter()->unique()->values()->toArray();
            $teacher->subjects = $subjects;
            unset($teacher->subjectAssignments);

            return $teacher;
        });

        return [
            'success' => true,
            'data' => $teachers->items(),
            'pagination' => $this->toPagination($teachers),
        ];
    }

    public function teacherShow(int $id): array
    {
        $teacher = User::where('id', $id)
            ->whereHas('roles', function ($q) {
                $q->where('name', 'teacher');
            })
            ->with(['subjectAssignments' => function ($q) {
                $q->where('status', true)
                    ->with([
                        'subject:id,name',
                        'section:id,name,grade_id',
                        'section.grade:id,name',
                        'academicPeriod:id,name',
                    ])
                    ->withCount(['tasks', 'students']);
            }])
            ->first();

        if (! $teacher) {
            return [
                'success' => false,
                'message' => 'Profesor no encontrado',
            ];
        }

        $totalTasks = 0;
        $totalStudents = 0;
        $pendingSubmissions = 0;

        foreach ($teacher->subjectAssignments as $assignment) {
            $totalTasks += $assignment->tasks_count;
            $totalStudents += $assignment->students_count;

            $pending = $assignment->tasks()
                ->whereHas('submissions', function ($q) {
                    $q->where('status', 'submitted');
                })
                ->count();
            $pendingSubmissions += $pending;
            $assignment->pending_submissions = $pending;
        }

        $teacher->stats = [
            'total_assignments' => $teacher->subjectAssignments->count(),
            'total_tasks' => $totalTasks,
            'total_students' => $totalStudents,
            'pending_submissions' => $pendingSubmissions,
        ];

        $teacher->assignments = $teacher->subjectAssignments;
        unset($teacher->subjectAssignments);

        return [
            'success' => true,
            'data' => $teacher,
        ];
    }

    public function tasksOverview(Request $request): array
    {
        $query = Task::with([
            'subjectAssignment.teacher:id,name',
            'subjectAssignment.subject:id,name',
            'subjectAssignment.section:id,name',
        ])
            ->withCount([
                'submissions',
                'submissions as pending_count' => function ($q) {
                    $q->where('status', 'submitted');
                },
                'submissions as graded_count' => function ($q) {
                    $q->where('status', 'graded');
                },
            ]);

        if ($request->filter === 'pending') {
            $query->whereHas('submissions', function ($q) {
                $q->where('status', 'submitted');
            });
        } elseif ($request->filter === 'overdue') {
            $query->where('due_date', '<', now())
                ->where('is_published', true);
        } elseif ($request->filter === 'draft') {
            $query->where('is_published', false);
        }

        $perPage = app(PaginationService::class)->perPage($request);
        $tasks = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $tasks->getCollection()->transform(function ($task) {
            return [
                'id' => $task->id,
                'title' => $task->title,
                'type' => $task->type,
                'due_date' => $task->due_date,
                'is_published' => $task->is_published,
                'teacher' => $task->subjectAssignment?->teacher,
                'subject' => $task->subjectAssignment?->subject,
                'section' => $task->subjectAssignment?->section,
                'submissions_count' => $task->submissions_count,
                'pending_count' => $task->pending_count,
                'graded_count' => $task->graded_count,
            ];
        });

        $stats = [
            'total_tasks' => Task::count(),
            'published_tasks' => Task::where('is_published', true)->count(),
            'pending_submissions' => TaskSubmission::where('status', 'submitted')->count(),
            'overdue_tasks' => Task::where('due_date', '<', now())
                ->where('is_published', true)
                ->count(),
        ];

        return [
            'success' => true,
            'data' => $tasks->items(),
            'stats' => $stats,
            'pagination' => $this->toPagination($tasks),
        ];
    }

    public function scoresOverview(Request $request): array
    {
        $termId = $request->get('term_id');

        if (! $termId) {
            $term = Term::whereHas('academicPeriod', function ($q) {
                $q->where('status', 'active');
            })->first();

            if (! $term) {
                $term = Term::first();
            }
            $termId = $term?->id;
        }

        $query = SubjectAssignment::query()
            ->with([
                'subject:id,name',
                'section:id,name,grade_id',
                'section.grade:id,name',
                'section.enrollments' => function ($q) {
                    $q->where('status', 'active');
                },
                'teacher:id,name',
            ]);

        $perPage = app(PaginationService::class)->perPage($request);
        $assignments = $query->paginate($perPage);

        $sectionsWithScores = 0;
        $totalAverage = 0;
        $averageCount = 0;
        $studentsBelowPassing = 0;

        $assignments->getCollection()->transform(function ($assignment) use ($termId, &$sectionsWithScores, &$totalAverage, &$averageCount, &$studentsBelowPassing) {
            $scores = StudentScore::where('subject_assignment_id', $assignment->id)
                ->where('term_id', $termId)
                ->get();

            $scoresEntered = $scores->count();
            $avgScore = $scores->avg('score') ?? 0;

            if ($scoresEntered > 0) {
                $sectionsWithScores++;
                $totalAverage += $avgScore;
                $averageCount++;
                $studentsBelowPassing += $scores->where('score', '<', 10)->count();
            }

            return [
                'section_id' => $assignment->section_id,
                'section_name' => $assignment->section?->name,
                'grade_name' => $assignment->section?->grade?->name,
                'subject_name' => $assignment->subject?->name,
                'teacher_name' => $assignment->teacher?->name,
                'students_count' => $assignment->section?->enrollments?->count() ?? 0,
                'scores_entered' => $scoresEntered,
                'average_score' => round($avgScore, 1),
            ];
        });

        $stats = [
            'total_sections' => $assignments->total(),
            'sections_with_scores' => $sectionsWithScores,
            'average_score' => $averageCount > 0 ? round($totalAverage / $averageCount, 1) : 0,
            'students_below_passing' => $studentsBelowPassing,
        ];

        return [
            'success' => true,
            'data' => $assignments->items(),
            'stats' => $stats,
            'pagination' => $this->toPagination($assignments),
        ];
    }

    private function toPagination($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'from' => $paginator->firstItem(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'to' => $paginator->lastItem(),
            'total' => $paginator->total(),
        ];
    }
}
