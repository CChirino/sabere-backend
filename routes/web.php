<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\Academic\AcademicPeriodController;
use App\Http\Controllers\Web\Academic\AssignmentController;
use App\Http\Controllers\Web\Academic\EnrollmentController;
use App\Http\Controllers\Web\Academic\GradeController;
use App\Http\Controllers\Web\Academic\ScheduleController;
use App\Http\Controllers\Web\Academic\SectionController;
use App\Http\Controllers\Web\Academic\SubjectController;
use App\Http\Controllers\Web\Admin\BulkNotificationController;
use App\Http\Controllers\Web\Admin\CircularController;
use App\Http\Controllers\Web\Admin\EventController;
use App\Http\Controllers\Web\Admin\HelpController as AdminHelpController;
use App\Http\Controllers\Web\Admin\ReenrollmentController as AdminReenrollmentController;
use App\Http\Controllers\Web\Admin\UserController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\HelpController;
use App\Http\Controllers\Web\Student\CircularController as StudentCircularController;
use App\Http\Controllers\Web\Student\ReenrollmentController as StudentReenrollmentController;
use App\Http\Controllers\Web\Student\SectionChatController;
use App\Http\Controllers\Web\Student\SyllabusController as StudentSyllabusController;
use App\Http\Controllers\Web\Teacher\AttendanceController;
use App\Http\Controllers\Web\Teacher\ScoreController;
use App\Http\Controllers\Web\Teacher\SectionChatController as TeacherSectionChatController;
use App\Http\Controllers\Web\Teacher\SyllabusController as TeacherSyllabusController;
use App\Http\Controllers\Web\Teacher\TaskController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', '/login');

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar'])->name('profile.avatar.upload');
    Route::delete('/profile/avatar', [ProfileController::class, 'removeAvatar'])->name('profile.avatar.remove');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Admin routes
    Route::prefix('admin')->name('admin.')->middleware('role:admin|director')->group(function () {
        Route::get('/users', fn () => Inertia::render('Admin/Users/Index'))->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::post('/users/import', [UserController::class, 'import'])->name('users.import');
        Route::get('/users/template', [UserController::class, 'downloadTemplate'])->name('users.template');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/roles', fn () => Inertia::render('Admin/Roles/Index'))->name('roles.index')->middleware('role:admin');

        // Admin Reenrollment Routes
        Route::get('/reenrollments', [AdminReenrollmentController::class, 'index'])->name('reenrollments.index');
        Route::get('/reenrollments/statistics', [AdminReenrollmentController::class, 'statistics'])->name('reenrollments.statistics');
        Route::post('/reenrollments/{reenrollment}/approve', [AdminReenrollmentController::class, 'approve'])->name('reenrollments.approve');
        Route::post('/reenrollments/{reenrollment}/reject', [AdminReenrollmentController::class, 'reject'])->name('reenrollments.reject');

        // Circulars
        Route::get('/circulars', [CircularController::class, 'index'])->name('circulars.index');
        Route::post('/circulars', [CircularController::class, 'store'])->name('circulars.store');
        Route::put('/circulars/{id}', [CircularController::class, 'update'])->name('circulars.update');
        Route::delete('/circulars/{id}', [CircularController::class, 'destroy'])->name('circulars.destroy');
        Route::post('/circulars/{id}/send', [CircularController::class, 'sendNow'])->name('circulars.send');

        // Help
        Route::get('/help', [AdminHelpController::class, 'index'])->name('help.index');
        Route::post('/help/categories', [AdminHelpController::class, 'storeCategory'])->name('help.categories.store');
        Route::put('/help/categories/{id}', [AdminHelpController::class, 'updateCategory'])->name('help.categories.update');
        Route::delete('/help/categories/{id}', [AdminHelpController::class, 'destroyCategory'])->name('help.categories.destroy');
        Route::post('/help/articles', [AdminHelpController::class, 'storeArticle'])->name('help.articles.store');
        Route::put('/help/articles/{id}', [AdminHelpController::class, 'updateArticle'])->name('help.articles.update');
        Route::delete('/help/articles/{id}', [AdminHelpController::class, 'destroyArticle'])->name('help.articles.destroy');
        Route::get('/help/suggestions', [AdminHelpController::class, 'suggestions'])->name('help.suggestions');
        Route::put('/help/suggestions/{id}', [AdminHelpController::class, 'respondSuggestion'])->name('help.suggestions.respond');

        // Admin Bulk Notifications (MED-06: máx 3 envíos masivos por hora)
        Route::get('/bulk-notifications', [BulkNotificationController::class, 'index'])->name('notifications.bulk');
        Route::post('/bulk-notifications', [BulkNotificationController::class, 'send'])->name('notifications.send');
        Route::get('/notifications/history', [BulkNotificationController::class, 'history'])->name('notifications.history');

        // Eventos (gestión)
        Route::get('/events', fn () => Inertia::render('Admin/Events/Index'))->name('events.index');
        Route::post('/events', [EventController::class, 'store'])->name('events.store');
        Route::put('/events/{event}', [EventController::class, 'update'])->name('events.update');
        Route::delete('/events/{event}', [EventController::class, 'destroy'])->name('events.destroy');
    });

    // Academic routes
    Route::prefix('academic')->name('academic.')->middleware('role:admin|director|coordinator|teacher')->group(function () {
        // Períodos Académicos
        Route::get('/periods', fn () => Inertia::render('Academic/Periods/Index'))->name('periods.index');
        Route::post('/periods', [AcademicPeriodController::class, 'store'])->name('periods.store');
        Route::put('/periods/{period}', [AcademicPeriodController::class, 'update'])->name('periods.update');
        Route::delete('/periods/{period}', [AcademicPeriodController::class, 'destroy'])->name('periods.destroy');

        // Grados y Niveles
        Route::get('/grades', fn () => Inertia::render('Academic/Grades/Index'))->name('grades.index');
        Route::post('/grades', [GradeController::class, 'store'])->name('grades.store');
        Route::put('/grades/{grade}', [GradeController::class, 'update'])->name('grades.update');
        Route::delete('/grades/{grade}', [GradeController::class, 'destroy'])->name('grades.destroy');

        // Secciones
        Route::get('/sections', fn () => Inertia::render('Academic/Sections/Index'))->name('sections.index');
        Route::post('/sections', [SectionController::class, 'store'])->name('sections.store');
        Route::put('/sections/{section}', [SectionController::class, 'update'])->name('sections.update');
        Route::delete('/sections/{section}', [SectionController::class, 'destroy'])->name('sections.destroy');

        // Materias
        Route::get('/subjects', fn () => Inertia::render('Academic/Subjects/Index'))->name('subjects.index');
        Route::post('/subjects', [SubjectController::class, 'store'])->name('subjects.store');
        Route::put('/subjects/{subject}', [SubjectController::class, 'update'])->name('subjects.update');
        Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy'])->name('subjects.destroy');

        // Inscripciones
        Route::get('/enrollments', fn () => Inertia::render('Academic/Enrollments/Index'))->name('enrollments.index');
        Route::post('/enrollments', [EnrollmentController::class, 'store'])->name('enrollments.store');
        Route::put('/enrollments/{enrollment}', [EnrollmentController::class, 'update'])->name('enrollments.update');
        Route::delete('/enrollments/{enrollment}', [EnrollmentController::class, 'destroy'])->name('enrollments.destroy');

        // Asignaciones (Profesor-Materia-Sección)
        Route::get('/assignments', fn () => Inertia::render('Academic/Assignments/Index'))->name('assignments.index');
        Route::post('/assignments', [AssignmentController::class, 'store'])->name('assignments.store');
        Route::put('/assignments/{assignment}', [AssignmentController::class, 'update'])->name('assignments.update');
        Route::delete('/assignments/{assignment}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');

        // Horarios
        Route::get('/schedules', fn () => Inertia::render('Academic/Schedules/Index'))->name('schedules.index');
        Route::get('/schedules/section/{id}', fn ($id) => Inertia::render('Academic/Schedules/Section', ['sectionId' => $id]))->name('schedules.section');
        Route::post('/schedules', [ScheduleController::class, 'store'])->name('schedules.store');
        Route::put('/schedules/{schedule}', [ScheduleController::class, 'update'])->name('schedules.update');
        Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy'])->name('schedules.destroy');
    });

    // Coordinator routes
    Route::prefix('coordinator')->name('coordinator.')->middleware('role:admin|director|coordinator')->group(function () {
        // Gestión de profesores
        Route::get('/teachers', fn () => Inertia::render('Coordinator/Teachers/Index'))->name('teachers.index');
        Route::get('/teachers/{id}', fn ($id) => Inertia::render('Coordinator/Teachers/Show', ['teacherId' => (int) $id]))->name('teachers.show');

        // Seguimiento de tareas
        Route::get('/tasks-overview', fn () => Inertia::render('Coordinator/TasksOverview'))->name('tasks-overview');

        // Seguimiento de notas
        Route::get('/scores-overview', fn () => Inertia::render('Coordinator/ScoresOverview'))->name('scores-overview');

        // Reportes
        Route::get('/reports', fn () => Inertia::render('Coordinator/Reports'))->name('reports');
    });

    // Teacher routes (also accessible by admin, director, coordinator)
    Route::prefix('teacher')->name('teacher.')->middleware('role:admin|director|coordinator|teacher')->group(function () {
        Route::get('/assignments', fn () => Inertia::render('Teacher/Assignments'))->name('assignments');

        Route::get('/tasks', fn () => Inertia::render('Teacher/Tasks/Index'))->name('tasks.index');
        Route::get('/tasks/create', fn () => Inertia::render('Teacher/Tasks/Create'))->name('tasks.create');
        Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
        Route::get('/tasks/{id}', fn ($id) => Inertia::render('Teacher/Tasks/Show', ['taskId' => (int) $id]))->name('tasks.show');
        Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
        Route::post('/tasks/{task}/toggle-publish', [TaskController::class, 'togglePublish'])->name('tasks.toggle-publish');
        Route::get('/tasks/{id}/submissions', fn ($id) => Inertia::render('Teacher/Tasks/Submissions', ['taskId' => (int) $id]))->name('tasks.submissions');

        // Scores routes
        Route::get('/scores', fn () => Inertia::render('Teacher/Scores/Index'))->name('scores.index');
        Route::get('/scores/assignment/{id}', fn ($id) => Inertia::render('Teacher/Scores/Assignment', ['assignmentId' => (int) $id]))->name('scores.assignment');
        Route::post('/scores', [ScoreController::class, 'store'])->name('scores.store');
        Route::post('/scores/bulk', [ScoreController::class, 'storeBulk'])->name('scores.store-bulk');
        Route::post('/scores/finalize', [ScoreController::class, 'finalize'])->name('scores.finalize');

        // Chat de sección para profesores
        Route::get('/chat', [TeacherSectionChatController::class, 'index'])->name('chat.index');
        Route::get('/chat/{section}', [TeacherSectionChatController::class, 'show'])->name('chat.show');
        Route::post('/chat/{section}', [TeacherSectionChatController::class, 'store'])->name('chat.store');
        Route::delete('/chat/{message}', [TeacherSectionChatController::class, 'destroy'])->name('chat.destroy');

        // Syllabi routes (Cronogramas)
        Route::get('/syllabi', [TeacherSyllabusController::class, 'index'])->name('syllabi.index');
        Route::get('/syllabi/create', [TeacherSyllabusController::class, 'create'])->name('syllabi.create');
        Route::post('/syllabi', [TeacherSyllabusController::class, 'store'])->name('syllabi.store');
        Route::get('/syllabi/{id}', [TeacherSyllabusController::class, 'show'])->name('syllabi.show');
        Route::get('/syllabi/{id}/edit', [TeacherSyllabusController::class, 'edit'])->name('syllabi.edit');
        Route::put('/syllabi/{id}', [TeacherSyllabusController::class, 'update'])->name('syllabi.update');
        Route::delete('/syllabi/{id}', [TeacherSyllabusController::class, 'destroy'])->name('syllabi.destroy');
        Route::get('/syllabi/{id}/download', [TeacherSyllabusController::class, 'download'])->name('syllabi.download');
        Route::post('/syllabi/{id}/toggle-publish', [TeacherSyllabusController::class, 'togglePublish'])->name('syllabi.toggle-publish');

        // Attendance routes
        Route::get('/attendance', fn () => Inertia::render('Teacher/Attendance/Index'))->name('attendance.index');
        Route::get('/attendance/section/{id}', fn ($id) => Inertia::render('Teacher/Attendance/Section', ['sectionId' => (int) $id]))->name('attendance.section');
        Route::get('/attendance/section/{id}/report', fn ($id) => Inertia::render('Teacher/Attendance/Report', ['sectionId' => (int) $id]))->name('attendance.report');
        Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    });

    // Student routes
    Route::prefix('student')->name('student.')->middleware('role:student')->group(function () {
        Route::get('/schedule', fn () => Inertia::render('Student/Schedule'))->name('schedule');
        Route::get('/tasks', fn () => Inertia::render('Student/Tasks/Index'))->name('tasks');
        Route::get('/tasks/{id}', fn ($id) => Inertia::render('Student/Tasks/Show', ['taskId' => (int) $id]))->name('tasks.show');
        Route::get('/scores', fn () => Inertia::render('Student/Scores'))->name('scores');

        // Reinscripciones
        Route::get('/reenrollment', [StudentReenrollmentController::class, 'create'])->name('reenrollment.create');
        Route::post('/reenrollment', [StudentReenrollmentController::class, 'store'])->name('reenrollment.store');
        Route::get('/reenrollment/status', [StudentReenrollmentController::class, 'status'])->name('reenrollment.status');
        Route::post('/reenrollment/{reenrollment}/cancel', [StudentReenrollmentController::class, 'cancel'])->name('reenrollment.cancel');

        // Syllabi routes (Cronogramas - solo lectura)
        Route::get('/syllabi', [StudentSyllabusController::class, 'index'])->name('syllabi.index');
        Route::get('/syllabi/{id}', [StudentSyllabusController::class, 'show'])->name('syllabi.show');
        Route::get('/syllabi/{id}/download', [StudentSyllabusController::class, 'download'])->name('syllabi.download');

        // Chat de sección
        Route::get('/chat', [SectionChatController::class, 'index'])->name('chat');
        Route::post('/chat', [SectionChatController::class, 'store'])->name('chat.store');
        Route::delete('/chat/{message}', [SectionChatController::class, 'destroy'])->name('chat.destroy');

    });

    // Circulars (lectura para todos los roles autenticados)
    Route::get('/circulars', [StudentCircularController::class, 'index'])->name('circulars.index');
    Route::get('/circulars/{id}', [StudentCircularController::class, 'show'])->name('circulars.show');

    // Help routes (visible para todos los roles autenticados)
    Route::get('/help', [HelpController::class, 'index'])->name('help.index');
    Route::get('/help/search', [HelpController::class, 'search'])->name('help.search');
    Route::get('/help/{slug}', [HelpController::class, 'show'])->name('help.show');
    Route::post('/help/suggestions', [HelpController::class, 'storeSuggestion'])->name('help.suggestions');

    // Events calendar (visible para todos los roles autenticados)
    Route::get('/events', fn () => Inertia::render('Events/Calendar'))->name('events.calendar');

    // Guardian routes
    Route::prefix('guardian')->name('guardian.')->middleware('role:guardian')->group(function () {
        Route::get('/students', fn () => Inertia::render('Guardian/Students'))->name('students');
        Route::get('/students/{id}/scores', fn ($id) => Inertia::render('Guardian/StudentScores', ['studentId' => (int) $id]))->name('students.scores');
        Route::get('/students/{id}/tasks', fn ($id) => Inertia::render('Guardian/StudentTasks', ['studentId' => (int) $id]))->name('students.tasks');
        Route::get('/students/{id}/tasks/{taskId}', fn ($id, $taskId) => Inertia::render('Guardian/StudentTaskShow', ['studentId' => (int) $id, 'taskId' => (int) $taskId]))->name('students.tasks.show');
        Route::get('/students/{id}/schedule', fn ($id) => Inertia::render('Guardian/StudentSchedule', ['studentId' => (int) $id]))->name('students.schedule');
    });
});

require __DIR__.'/auth.php';
