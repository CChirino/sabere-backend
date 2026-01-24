<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\Admin\UserController;
use App\Http\Controllers\Web\Academic\AcademicPeriodController;
use App\Http\Controllers\Web\Academic\GradeController;
use App\Http\Controllers\Web\Academic\SectionController;
use App\Http\Controllers\Web\Academic\SubjectController;
use App\Http\Controllers\Web\Academic\EnrollmentController;
use App\Http\Controllers\Web\Academic\AssignmentController;
use App\Http\Controllers\Web\Academic\ScheduleController;
use App\Http\Controllers\Web\Teacher\ScoreController;
use App\Http\Controllers\Web\Teacher\TaskController;
use App\Http\Controllers\Web\Teacher\AttendanceController;
use App\Http\Controllers\Web\Student\SectionChatController;
use App\Http\Controllers\Web\Teacher\SectionChatController as TeacherSectionChatController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Landing', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

Route::get('/demo', function () {
    return Inertia::render('Demo');
})->name('demo');

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Admin routes
    Route::prefix('admin')->name('admin.')->middleware('role:admin|director')->group(function () {
        Route::get('/users', fn() => Inertia::render('Admin/Users/Index'))->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::post('/users/import', [UserController::class, 'import'])->name('users.import');
        Route::get('/users/template', [UserController::class, 'downloadTemplate'])->name('users.template');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        
        Route::get('/roles', fn() => Inertia::render('Admin/Roles/Index'))->name('roles.index')->middleware('role:admin');
    });

    // Academic routes
    Route::prefix('academic')->name('academic.')->middleware('role:admin|director|coordinator|teacher')->group(function () {
        // Períodos Académicos
        Route::get('/periods', fn() => Inertia::render('Academic/Periods/Index'))->name('periods.index');
        Route::post('/periods', [AcademicPeriodController::class, 'store'])->name('periods.store');
        Route::put('/periods/{period}', [AcademicPeriodController::class, 'update'])->name('periods.update');
        Route::delete('/periods/{period}', [AcademicPeriodController::class, 'destroy'])->name('periods.destroy');

        // Grados y Niveles
        Route::get('/grades', fn() => Inertia::render('Academic/Grades/Index'))->name('grades.index');
        Route::post('/grades', [GradeController::class, 'store'])->name('grades.store');
        Route::put('/grades/{grade}', [GradeController::class, 'update'])->name('grades.update');
        Route::delete('/grades/{grade}', [GradeController::class, 'destroy'])->name('grades.destroy');

        // Secciones
        Route::get('/sections', fn() => Inertia::render('Academic/Sections/Index'))->name('sections.index');
        Route::post('/sections', [SectionController::class, 'store'])->name('sections.store');
        Route::put('/sections/{section}', [SectionController::class, 'update'])->name('sections.update');
        Route::delete('/sections/{section}', [SectionController::class, 'destroy'])->name('sections.destroy');

        // Materias
        Route::get('/subjects', fn() => Inertia::render('Academic/Subjects/Index'))->name('subjects.index');
        Route::post('/subjects', [SubjectController::class, 'store'])->name('subjects.store');
        Route::put('/subjects/{subject}', [SubjectController::class, 'update'])->name('subjects.update');
        Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy'])->name('subjects.destroy');

        // Inscripciones
        Route::get('/enrollments', fn() => Inertia::render('Academic/Enrollments/Index'))->name('enrollments.index');
        Route::post('/enrollments', [EnrollmentController::class, 'store'])->name('enrollments.store');
        Route::put('/enrollments/{enrollment}', [EnrollmentController::class, 'update'])->name('enrollments.update');
        Route::delete('/enrollments/{enrollment}', [EnrollmentController::class, 'destroy'])->name('enrollments.destroy');

        // Asignaciones (Profesor-Materia-Sección)
        Route::get('/assignments', fn() => Inertia::render('Academic/Assignments/Index'))->name('assignments.index');
        Route::post('/assignments', [AssignmentController::class, 'store'])->name('assignments.store');
        Route::put('/assignments/{assignment}', [AssignmentController::class, 'update'])->name('assignments.update');
        Route::delete('/assignments/{assignment}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');

        // Horarios
        Route::get('/schedules', fn() => Inertia::render('Academic/Schedules/Index'))->name('schedules.index');
        Route::get('/schedules/section/{id}', fn($id) => Inertia::render('Academic/Schedules/Section', ['sectionId' => $id]))->name('schedules.section');
        Route::post('/schedules', [ScheduleController::class, 'store'])->name('schedules.store');
        Route::put('/schedules/{schedule}', [ScheduleController::class, 'update'])->name('schedules.update');
        Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy'])->name('schedules.destroy');
    });

    // Coordinator routes
    Route::prefix('coordinator')->name('coordinator.')->middleware('role:admin|director|coordinator')->group(function () {
        // Gestión de profesores
        Route::get('/teachers', fn() => Inertia::render('Coordinator/Teachers/Index'))->name('teachers.index');
        Route::get('/teachers/{id}', fn($id) => Inertia::render('Coordinator/Teachers/Show', ['teacherId' => (int)$id]))->name('teachers.show');
        
        // Seguimiento de tareas
        Route::get('/tasks-overview', fn() => Inertia::render('Coordinator/TasksOverview'))->name('tasks-overview');
        
        // Seguimiento de notas
        Route::get('/scores-overview', fn() => Inertia::render('Coordinator/ScoresOverview'))->name('scores-overview');
        
        // Reportes
        Route::get('/reports', fn() => Inertia::render('Coordinator/Reports'))->name('reports');
    });

    // Teacher routes (also accessible by admin, director, coordinator)
    Route::prefix('teacher')->name('teacher.')->middleware('role:admin|director|coordinator|teacher')->group(function () {
        Route::get('/assignments', fn() => Inertia::render('Teacher/Assignments'))->name('assignments');
        
        Route::get('/tasks', fn() => Inertia::render('Teacher/Tasks/Index'))->name('tasks.index');
        Route::get('/tasks/create', fn() => Inertia::render('Teacher/Tasks/Create'))->name('tasks.create');
        Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
        Route::get('/tasks/{id}', fn($id) => Inertia::render('Teacher/Tasks/Show', ['taskId' => (int)$id]))->name('tasks.show');
        Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
        Route::post('/tasks/{task}/toggle-publish', [TaskController::class, 'togglePublish'])->name('tasks.toggle-publish');
        Route::get('/tasks/{id}/submissions', fn($id) => Inertia::render('Teacher/Tasks/Submissions', ['taskId' => (int)$id]))->name('tasks.submissions');
        
        // Scores routes
        Route::get('/scores', fn() => Inertia::render('Teacher/Scores/Index'))->name('scores.index');
        Route::get('/scores/assignment/{id}', fn($id) => Inertia::render('Teacher/Scores/Assignment', ['assignmentId' => (int)$id]))->name('scores.assignment');
        Route::post('/scores', [ScoreController::class, 'store'])->name('scores.store');
        Route::post('/scores/bulk', [ScoreController::class, 'storeBulk'])->name('scores.store-bulk');
        Route::post('/scores/finalize', [ScoreController::class, 'finalize'])->name('scores.finalize');
        
        // Chat de sección para profesores
        Route::get('/chat', [TeacherSectionChatController::class, 'index'])->name('chat.index');
        Route::get('/chat/{section}', [TeacherSectionChatController::class, 'show'])->name('chat.show');
        Route::post('/chat/{section}', [TeacherSectionChatController::class, 'store'])->name('chat.store');
        Route::delete('/chat/{message}', [TeacherSectionChatController::class, 'destroy'])->name('chat.destroy');
        
        // Attendance routes
        Route::get('/attendance', fn() => Inertia::render('Teacher/Attendance/Index'))->name('attendance.index');
        Route::get('/attendance/section/{id}', fn($id) => Inertia::render('Teacher/Attendance/Section', ['sectionId' => (int)$id]))->name('attendance.section');
        Route::get('/attendance/section/{id}/report', fn($id) => Inertia::render('Teacher/Attendance/Report', ['sectionId' => (int)$id]))->name('attendance.report');
        Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    });

    // Student routes
    Route::prefix('student')->name('student.')->middleware('role:student')->group(function () {
        Route::get('/schedule', fn() => Inertia::render('Student/Schedule'))->name('schedule');
        Route::get('/tasks', fn() => Inertia::render('Student/Tasks/Index'))->name('tasks');
        Route::get('/tasks/{id}', fn($id) => Inertia::render('Student/Tasks/Show', ['taskId' => (int)$id]))->name('tasks.show');
        Route::get('/scores', fn() => Inertia::render('Student/Scores'))->name('scores');
        
        // Chat de sección
        Route::get('/chat', [SectionChatController::class, 'index'])->name('chat');
        Route::post('/chat', [SectionChatController::class, 'store'])->name('chat.store');
        Route::delete('/chat/{message}', [SectionChatController::class, 'destroy'])->name('chat.destroy');
    });

    // Guardian routes
    Route::prefix('guardian')->name('guardian.')->middleware('role:guardian')->group(function () {
        Route::get('/students', fn() => Inertia::render('Guardian/Students'))->name('students');
        Route::get('/students/{id}/scores', fn($id) => Inertia::render('Guardian/StudentScores', ['studentId' => (int)$id]))->name('students.scores');
        Route::get('/students/{id}/tasks', fn($id) => Inertia::render('Guardian/StudentTasks', ['studentId' => (int)$id]))->name('students.tasks');
    });
});

require __DIR__.'/auth.php';
