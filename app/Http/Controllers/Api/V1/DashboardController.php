<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\SubjectAssignment;
use App\Models\Task;
use App\Models\TaskSubmission;
use App\Models\StudentScore;
use App\Models\AcademicPeriod;
use App\Models\Term;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Dashboard principal - redirige según el rol
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            return $this->adminDashboard();
        }

        if ($user->hasRole('director')) {
            return $this->directorDashboard();
        }

        if ($user->hasRole('coordinator')) {
            return $this->coordinatorDashboard();
        }

        if ($user->hasRole('teacher')) {
            return $this->teacherDashboard();
        }

        if ($user->hasRole('student')) {
            return $this->studentDashboard();
        }

        if ($user->hasRole('guardian')) {
            return $this->guardianDashboard();
        }

        return $this->sendError('Rol no reconocido', [], 403);
    }

    /**
     * Dashboard del Administrador
     */
    public function adminDashboard(): JsonResponse
    {
        $currentPeriod = AcademicPeriod::where('status', true)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first();

        $data = [
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

        return $this->sendResponse($data, 'Dashboard de administrador');
    }

    /**
     * Dashboard del Director
     */
    public function directorDashboard(): JsonResponse
    {
        $currentPeriod = AcademicPeriod::where('status', true)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first();

        $currentTerm = $currentPeriod ? Term::where('academic_period_id', $currentPeriod->id)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first() : null;

        $data = [
            'current_period' => $currentPeriod,
            'current_term' => $currentTerm,
            'stats' => [
                'total_students' => User::role('student')->count(),
                'total_teachers' => User::role('teacher')->count(),
                'active_enrollments' => $currentPeriod ? Enrollment::where('academic_period_id', $currentPeriod->id)->where('status', 'active')->count() : 0,
                'total_sections' => $currentPeriod ? Section::where('academic_period_id', $currentPeriod->id)->count() : 0,
                'total_subject_assignments' => $currentPeriod ? SubjectAssignment::where('academic_period_id', $currentPeriod->id)->count() : 0,
            ],
            'enrollments_by_level' => $currentPeriod ? $this->getEnrollmentsByLevel($currentPeriod->id) : [],
            'pending_tasks' => Task::where('is_published', true)
                ->whereDate('due_date', '>=', now())
                ->count(),
        ];

        return $this->sendResponse($data, 'Dashboard de director');
    }

    /**
     * Dashboard del Coordinador
     */
    public function coordinatorDashboard(): JsonResponse
    {
        $currentPeriod = AcademicPeriod::where('status', true)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first();

        $currentTerm = $currentPeriod ? Term::where('academic_period_id', $currentPeriod->id)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first() : null;

        $data = [
            'current_period' => $currentPeriod,
            'current_term' => $currentTerm,
            'stats' => [
                'total_teachers' => User::role('teacher')->count(),
                'total_students' => $currentPeriod ? Enrollment::where('academic_period_id', $currentPeriod->id)->where('status', 'active')->count() : 0,
                'total_sections' => $currentPeriod ? Section::where('academic_period_id', $currentPeriod->id)->count() : 0,
                'pending_grades' => $currentTerm ? $this->getPendingGradesCount($currentTerm->id) : 0,
            ],
            'sections' => $currentPeriod ? Section::with(['grade.educationLevel'])
                ->where('academic_period_id', $currentPeriod->id)
                ->withCount(['enrollments' => function ($q) {
                    $q->where('status', 'active');
                }])
                ->get() : [],
        ];

        return $this->sendResponse($data, 'Dashboard de coordinador');
    }

    /**
     * Dashboard del Profesor
     */
    public function teacherDashboard(): JsonResponse
    {
        $teacherId = Auth::id();

        $currentPeriod = AcademicPeriod::where('status', true)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first();

        $currentTerm = $currentPeriod ? Term::where('academic_period_id', $currentPeriod->id)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first() : null;

        // Asignaciones del profesor
        $assignments = SubjectAssignment::with(['subject', 'section.grade.educationLevel'])
            ->where('teacher_id', $teacherId)
            ->where('status', true)
            ->when($currentPeriod, function ($q) use ($currentPeriod) {
                $q->where('academic_period_id', $currentPeriod->id);
            })
            ->get();

        // Tareas pendientes de calificar
        $pendingSubmissions = TaskSubmission::whereHas('task.subjectAssignment', function ($q) use ($teacherId) {
            $q->where('teacher_id', $teacherId);
        })
            ->whereIn('status', ['submitted', 'late'])
            ->count();

        // Tareas próximas a vencer
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

        $data = [
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

        return $this->sendResponse($data, 'Dashboard de profesor');
    }

    /**
     * Dashboard del Estudiante
     */
    public function studentDashboard(): JsonResponse
    {
        $studentId = Auth::id();
        $student = Auth::user();

        // Inscripción activa
        $enrollment = Enrollment::with(['section.grade.educationLevel', 'academicPeriod'])
            ->where('student_id', $studentId)
            ->where('status', 'active')
            ->first();

        if (!$enrollment) {
            return $this->sendResponse([
                'message' => 'No tienes una inscripción activa',
                'enrollment' => null,
            ], 'Dashboard de estudiante');
        }

        $currentTerm = Term::where('academic_period_id', $enrollment->academic_period_id)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first();

        // Tareas pendientes
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

        // Calificaciones basadas en entregas de tareas calificadas
        $gradedSubmissions = TaskSubmission::with(['task.subjectAssignment.subject', 'task.term'])
            ->where('student_id', $studentId)
            ->where('status', 'graded')
            ->whereHas('task.subjectAssignment', function ($q) use ($enrollment) {
                $q->where('section_id', $enrollment->section_id);
            })
            ->get();

        // Agrupar por materia para mostrar en el dashboard
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

        // Promedio actual (normalizado a 20 puntos)
        $totalScore = $gradedSubmissions->sum('score');
        $totalMaxScore = $gradedSubmissions->sum(function ($sub) {
            return $sub->task->max_score;
        });
        $average = $totalMaxScore > 0 ? round(($totalScore / $totalMaxScore) * 20, 2) : null;

        // Materias del estudiante
        $subjects = SubjectAssignment::with(['subject', 'teacher'])
            ->where('section_id', $enrollment->section_id)
            ->where('status', true)
            ->get();

        $data = [
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

        return $this->sendResponse($data, 'Dashboard de estudiante');
    }

    /**
     * Dashboard del Representante
     */
    public function guardianDashboard(): JsonResponse
    {
        $guardianId = Auth::id();
        $guardian = Auth::user();

        // Estudiantes representados
        $students = $guardian->students()
            ->with(['enrollments' => function ($q) {
                $q->where('status', 'active')
                    ->with(['section.grade.educationLevel', 'academicPeriod']);
            }])
            ->get();

        $studentsData = $students->map(function ($student) {
            $enrollment = $student->enrollments->first();

            if (!$enrollment) {
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

            // Tareas pendientes
            $pendingTasks = Task::whereHas('subjectAssignment', function ($q) use ($enrollment) {
                $q->where('section_id', $enrollment->section_id);
            })
                ->where('is_published', true)
                ->whereDoesntHave('submissions', function ($q) use ($student) {
                    $q->where('student_id', $student->id)
                        ->whereIn('status', ['submitted', 'graded']);
                })
                ->count();

            // Promedio actual
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

        $data = [
            'students' => $studentsData,
            'total_students' => $students->count(),
        ];

        return $this->sendResponse($data, 'Dashboard de representante');
    }

    /**
     * Helper: Obtener inscripciones por nivel educativo
     */
    private function getEnrollmentsByLevel(int $periodId): array
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

    /**
     * Helper: Obtener cantidad de notas pendientes
     */
    private function getPendingGradesCount(int $termId): int
    {
        // Esto es una aproximación - cuenta estudiantes sin nota en alguna materia
        $term = Term::find($termId);
        if (!$term) return 0;

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
