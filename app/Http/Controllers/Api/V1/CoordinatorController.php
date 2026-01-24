<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Task;
use App\Models\SubjectAssignment;
use App\Models\StudentScore;
use App\Models\Section;
use App\Models\Term;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoordinatorController extends Controller
{
    /**
     * Listar profesores con sus estadísticas
     */
    public function teachers(Request $request): JsonResponse
    {
        $query = User::role('teacher')
            ->withCount(['subjectAssignments as assignments_count' => function ($q) {
                $q->where('status', true);
            }])
            ->with(['subjectAssignments' => function ($q) {
                $q->where('status', true)
                    ->with(['subject:id,name', 'section:id,name']);
            }]);

        // Búsqueda
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $teachers = $query->orderBy('name')->paginate($perPage);

        // Agregar materias únicas a cada profesor
        $teachers->getCollection()->transform(function ($teacher) {
            $subjects = $teacher->subjectAssignments->pluck('subject.name')->filter()->unique()->values()->toArray();
            $teacher->subjects = $subjects;
            unset($teacher->subjectAssignments);
            return $teacher;
        });

        return response()->json([
            'success' => true,
            'data' => $teachers->items(),
            'pagination' => [
                'current_page' => $teachers->currentPage(),
                'from' => $teachers->firstItem(),
                'last_page' => $teachers->lastPage(),
                'per_page' => $teachers->perPage(),
                'to' => $teachers->lastItem(),
                'total' => $teachers->total(),
            ],
        ]);
    }

    /**
     * Detalle de un profesor con sus asignaciones y estadísticas
     */
    public function teacherShow(int $id): JsonResponse
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

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Profesor no encontrado',
            ], 404);
        }

        // Calcular estadísticas
        $totalTasks = 0;
        $totalStudents = 0;
        $pendingSubmissions = 0;

        foreach ($teacher->subjectAssignments as $assignment) {
            $totalTasks += $assignment->tasks_count;
            $totalStudents += $assignment->students_count;
            
            // Contar entregas pendientes
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

        return response()->json([
            'success' => true,
            'data' => $teacher,
        ]);
    }

    /**
     * Resumen de tareas para el coordinador
     */
    public function tasksOverview(Request $request): JsonResponse
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

        // Filtros
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

        $perPage = $request->get('per_page', 15);
        $tasks = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Transformar datos
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

        // Estadísticas generales
        $stats = [
            'total_tasks' => Task::count(),
            'published_tasks' => Task::where('is_published', true)->count(),
            'pending_submissions' => \App\Models\TaskSubmission::where('status', 'submitted')->count(),
            'overdue_tasks' => Task::where('due_date', '<', now())
                ->where('is_published', true)
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $tasks->items(),
            'stats' => $stats,
            'pagination' => [
                'current_page' => $tasks->currentPage(),
                'from' => $tasks->firstItem(),
                'last_page' => $tasks->lastPage(),
                'per_page' => $tasks->perPage(),
                'to' => $tasks->lastItem(),
                'total' => $tasks->total(),
            ],
        ]);
    }

    /**
     * Resumen de notas para el coordinador
     */
    public function scoresOverview(Request $request): JsonResponse
    {
        $termId = $request->get('term_id');
        
        // Si no se especifica término, usar el primero disponible
        if (!$termId) {
            $term = Term::whereHas('academicPeriod', function ($q) {
                $q->where('status', 'active');
            })->first();
            
            // Si no hay término activo, buscar cualquier término
            if (!$term) {
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

        $perPage = $request->get('per_page', 15);
        $assignments = $query->paginate($perPage);

        // Calcular estadísticas de notas para cada asignación
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

        return response()->json([
            'success' => true,
            'data' => $assignments->items(),
            'stats' => $stats,
            'pagination' => [
                'current_page' => $assignments->currentPage(),
                'from' => $assignments->firstItem(),
                'last_page' => $assignments->lastPage(),
                'per_page' => $assignments->perPage(),
                'to' => $assignments->lastItem(),
                'total' => $assignments->total(),
            ],
        ]);
    }
}
