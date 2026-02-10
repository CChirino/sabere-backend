<?php

namespace App\Http\Controllers\Web;

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
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();
        $data = $this->getDashboardData($user);

        return Inertia::render('Dashboard', [
            'dashboardData' => $data,
        ]);
    }

    private function getDashboardData(User $user): array
    {
        $currentPeriod = AcademicPeriod::where('status', true)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first();

        $currentTerm = $currentPeriod ? Term::where('academic_period_id', $currentPeriod->id)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first() : null;

        $baseData = [
            'current_period' => $currentPeriod,
            'current_term' => $currentTerm,
        ];

        if ($user->hasRole('admin')) {
            return array_merge($baseData, $this->getAdminData($currentPeriod));
        }

        if ($user->hasRole('director')) {
            return array_merge($baseData, $this->getDirectorData($currentPeriod));
        }

        if ($user->hasRole('coordinator')) {
            return array_merge($baseData, $this->getCoordinatorData($currentPeriod, $currentTerm));
        }

        if ($user->hasRole('teacher')) {
            return array_merge($baseData, $this->getTeacherData($user, $currentPeriod));
        }

        if ($user->hasRole('student')) {
            return array_merge($baseData, $this->getStudentData($user, $currentTerm));
        }

        if ($user->hasRole('guardian')) {
            return array_merge($baseData, $this->getGuardianData($user));
        }

        return $baseData;
    }

    private function getAdminData(?AcademicPeriod $currentPeriod): array
    {
        return [
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

    private function getDirectorData(?AcademicPeriod $currentPeriod): array
    {
        return [
            'stats' => [
                'total_students' => User::role('student')->count(),
                'total_teachers' => User::role('teacher')->count(),
                'active_enrollments' => $currentPeriod ? Enrollment::where('academic_period_id', $currentPeriod->id)->where('status', 'active')->count() : 0,
                'total_sections' => $currentPeriod ? Section::where('academic_period_id', $currentPeriod->id)->count() : 0,
                'total_subject_assignments' => $currentPeriod ? SubjectAssignment::where('academic_period_id', $currentPeriod->id)->count() : 0,
            ],
            'pending_tasks' => Task::where('is_published', true)
                ->whereDate('due_date', '>=', now())
                ->count(),
        ];
    }

    private function getCoordinatorData(?AcademicPeriod $currentPeriod, ?Term $currentTerm): array
    {
        return [
            'stats' => [
                'total_teachers' => User::role('teacher')->count(),
                'total_students' => $currentPeriod ? Enrollment::where('academic_period_id', $currentPeriod->id)->where('status', 'active')->count() : 0,
                'total_sections' => $currentPeriod ? Section::where('academic_period_id', $currentPeriod->id)->count() : 0,
            ],
            'sections' => $currentPeriod ? Section::with(['grade.educationLevel'])
                ->where('academic_period_id', $currentPeriod->id)
                ->withCount(['enrollments' => function ($q) {
                    $q->where('status', 'active');
                }])
                ->get() : [],
        ];
    }

    private function getTeacherData(User $user, ?AcademicPeriod $currentPeriod): array
    {
        $assignments = SubjectAssignment::with(['subject', 'section.grade.educationLevel'])
            ->where('teacher_id', $user->id)
            ->where('status', true)
            ->when($currentPeriod, function ($q) use ($currentPeriod) {
                $q->where('academic_period_id', $currentPeriod->id);
            })
            ->get();

        $pendingSubmissions = TaskSubmission::whereHas('task.subjectAssignment', function ($q) use ($user) {
            $q->where('teacher_id', $user->id);
        })
            ->whereIn('status', ['submitted', 'late'])
            ->count();

        $upcomingTasks = Task::whereHas('subjectAssignment', function ($q) use ($user) {
            $q->where('teacher_id', $user->id);
        })
            ->where('is_published', true)
            ->whereDate('due_date', '>=', now())
            ->whereDate('due_date', '<=', now()->addDays(7))
            ->with(['subjectAssignment.subject', 'subjectAssignment.section.grade'])
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        return [
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

    private function getStudentData(User $user, ?Term $currentTerm): array
    {
        $enrollment = Enrollment::with(['section.grade.educationLevel', 'academicPeriod'])
            ->where('student_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$enrollment) {
            return [
                'message' => 'No tienes una inscripción activa',
                'enrollment' => null,
                'stats' => [],
            ];
        }

        $pendingTasks = Task::whereHas('subjectAssignment', function ($q) use ($enrollment) {
            $q->where('section_id', $enrollment->section_id);
        })
            ->where('is_published', true)
            ->whereDoesntHave('submissions', function ($q) use ($user) {
                $q->where('student_id', $user->id)
                    ->whereIn('status', ['submitted', 'graded']);
            })
            ->with(['subjectAssignment.subject'])
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        $currentScores = $currentTerm ? StudentScore::with(['subjectAssignment.subject'])
            ->where('student_id', $user->id)
            ->where('term_id', $currentTerm->id)
            ->get() : collect();

        $subjects = SubjectAssignment::with(['subject', 'teacher'])
            ->where('section_id', $enrollment->section_id)
            ->where('status', true)
            ->get();

        return [
            'enrollment' => $enrollment,
            'stats' => [
                'pending_tasks' => $pendingTasks->count(),
                'current_average' => $currentScores->avg('score') ? round($currentScores->avg('score'), 2) : null,
                'total_subjects' => $subjects->count(),
            ],
            'pending_tasks' => $pendingTasks,
            'current_scores' => $currentScores,
            'subjects' => $subjects,
        ];
    }

    private function getGuardianData(User $user): array
    {
        $students = $user->students()
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
            'stats' => [
                'total_students' => $students->count(),
            ],
        ];
    }

}
