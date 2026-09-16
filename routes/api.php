<?php

use App\Http\Controllers\Api\V1\Academic\DocumentController;
use App\Http\Controllers\Api\V1\Academic\ScheduleController;
use App\Http\Controllers\Api\V1\Academic\StudentGuardianController;
use App\Http\Controllers\Api\V1\Academic\StudentScoreController;
use App\Http\Controllers\Api\V1\Academic\TaskController;
use App\Http\Controllers\Api\V1\Academic\TaskSubmissionController;
use App\Http\Controllers\Api\V1\Admin\RoleController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Api\V1\AdmissionController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\SocialAuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DirectMessageController;
use App\Http\Controllers\Api\V1\DisciplinaryRecordController;
use App\Http\Controllers\Api\V1\IncidentTypeController;
use App\Http\Controllers\Api\V1\JustificationController;
use App\Http\Controllers\Api\V1\NotificationPreferenceController;
use App\Http\Controllers\Api\V1\PushSubscriptionController;
use App\Http\Controllers\Api\V1\StudentProfileController;
use App\Http\Controllers\Web\Teacher\AttendanceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public routes - con rate limiting
    // MED-04: Rate limiting más estricto — 3 intentos por minuto para autenticación
    Route::post('/register', [RegisterController::class, 'register'])->middleware('throttle:3,1');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:3,1');

    // Google OAuth routes
    Route::get('/login/google', [SocialAuthController::class, 'redirectToGoogle'])->middleware('throttle:5,1');
    Route::get('/login/google/callback', [SocialAuthController::class, 'handleGoogleCallback'])->middleware('throttle:5,1');

    // Verificación pública de documentos
    Route::get('/documents/verify/{hash}', [DocumentController::class, 'verify'])->name('documents.verify');

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [LoginController::class, 'logout']);

        // User routes
        Route::get('/user', function (Request $request) {
            return response()->json([
                'user' => $request->user(),
            ]);
        });

        // Push notifications
        Route::post('push-subscriptions', [PushSubscriptionController::class, 'store'])->name('push-subscriptions.store');
        Route::delete('push-subscriptions', [PushSubscriptionController::class, 'destroy'])->name('push-subscriptions.destroy');
        Route::get('notification-preferences', [NotificationPreferenceController::class, 'index'])->name('notification-preferences.index');
        Route::put('notification-preferences', [NotificationPreferenceController::class, 'update'])->name('notification-preferences.update');

        // Mensajería directa
        Route::get('messages', [DirectMessageController::class, 'index'])->name('api.messages.index');
        Route::get('messages/sent', [DirectMessageController::class, 'sent'])->name('api.messages.sent');
        Route::get('messages/recipients', [DirectMessageController::class, 'recipients'])->name('api.messages.recipients');
        Route::get('messages/unread-count', [DirectMessageController::class, 'unreadCount'])->name('api.messages.unread-count');
        Route::get('messages/{message}', [DirectMessageController::class, 'show'])->name('api.messages.show');
        Route::post('messages', [DirectMessageController::class, 'store'])->name('api.messages.store');
        Route::post('messages/{message}/read', [DirectMessageController::class, 'markAsRead'])->name('api.messages.read');

        // Admisiones
        Route::get('admissions', [AdmissionController::class, 'index'])->name('api.admissions.index');
        Route::post('admissions', [AdmissionController::class, 'store'])->name('api.admissions.store');
        Route::get('admissions/{admission}', [AdmissionController::class, 'show'])->name('api.admissions.show');
        Route::put('admissions/{admission}', [AdmissionController::class, 'update'])->name('api.admissions.update');
        Route::post('admissions/{admission}/approve', [AdmissionController::class, 'approve'])->name('api.admissions.approve');
        Route::post('admissions/{admission}/reject', [AdmissionController::class, 'reject'])->name('api.admissions.reject');
        Route::get('admissions/{admission}/suggest-sections', [AdmissionController::class, 'suggestSections'])->name('api.admissions.suggest-sections');

        // Ficha integral
        Route::get('students/{student}/profile', [StudentProfileController::class, 'show'])->name('api.students.profile.show');
        Route::put('students/{student}/profile', [StudentProfileController::class, 'update'])->name('api.students.profile.update');
        Route::post('students/{student}/documents', [StudentProfileController::class, 'storeDocument'])->name('api.students.documents.store');
        Route::delete('students/{student}/documents/{document}', [StudentProfileController::class, 'destroyDocument'])->name('api.students.documents.destroy');
        Route::post('students/{student}/documents/{document}/verify', [StudentProfileController::class, 'verifyDocument'])->name('api.students.documents.verify');

        // Disciplina
        Route::get('incident-types', [IncidentTypeController::class, 'index'])->name('api.incident-types.index');
        Route::get('disciplinary-records', [DisciplinaryRecordController::class, 'index'])->name('api.disciplinary-records.index');
        Route::post('disciplinary-records', [DisciplinaryRecordController::class, 'store'])->name('api.disciplinary-records.store');
        Route::get('disciplinary-records/{disciplinaryRecord}', [DisciplinaryRecordController::class, 'show'])->name('api.disciplinary-records.show');
        Route::put('disciplinary-records/{disciplinaryRecord}', [DisciplinaryRecordController::class, 'update'])->name('api.disciplinary-records.update');
        Route::delete('disciplinary-records/{disciplinaryRecord}', [DisciplinaryRecordController::class, 'destroy'])->name('api.disciplinary-records.destroy');

        // Justificativos
        Route::get('justifications', [JustificationController::class, 'index'])->name('api.justifications.index');
        Route::post('justifications', [JustificationController::class, 'store'])->name('api.justifications.store');
        Route::get('justifications/{justification}', [JustificationController::class, 'show'])->name('api.justifications.show');
        Route::post('justifications/{justification}/approve', [JustificationController::class, 'approve'])->name('api.justifications.approve');
        Route::post('justifications/{justification}/reject', [JustificationController::class, 'reject'])->name('api.justifications.reject');
        Route::delete('justifications/{justification}', [JustificationController::class, 'destroy'])->name('api.justifications.destroy');
    });
});

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // Ruta para obtener el usuario autenticado
    Route::get('/user', function (Request $request) {
        return $request->user()->load('roles');
    });

    // Dashboard - accesible para todos los usuarios autenticados
    Route::get('v1/dashboard', [DashboardController::class, 'index']);

    // Indicadores - solo staff
    Route::get('v1/dashboard/indicators', [DashboardController::class, 'indicators'])
        ->name('api.dashboard.indicators')
        ->middleware('role:admin|director|coordinator');

    // Rutas de administración (solo para admin y director)
    Route::prefix('v1/admin')->middleware(['role:admin|director'])->group(function () {
        // Rutas para gestión de roles
        Route::apiResource('roles', RoleController::class);

        // Rutas para gestión de usuarios
        Route::apiResource('users', UserController::class);

        // Rutas adicionales para usuarios
        Route::post('users/{user}/assign-roles', [UserController::class, 'assignRoles'])
            ->name('users.assign-roles');
        Route::post('users/{user}/remove-roles', [UserController::class, 'removeRoles'])
            ->name('users.remove-roles');
    });

    // Rutas académicas de SOLO LECTURA - accesible para todos los roles autenticados incluyendo estudiantes
    Route::middleware(['role:admin|director|coordinator|teacher|student'])->group(function () {
        require __DIR__.'/academic_read.php';
    });

    // Rutas académicas de ESCRITURA - solo para admin, director, coordinator, teacher
    Route::middleware(['role:admin|director|coordinator|teacher'])->group(function () {
        require __DIR__.'/academic.php';
    });

    // Rutas para profesores
    Route::prefix('v1/teacher')->middleware('role:teacher')->group(function () {
        Route::get('my-assignments', [DashboardController::class, 'teacherDashboard']);
        Route::get('pending-submissions', [TaskSubmissionController::class, 'pendingForTeacher']);
    });

    // Rutas para estudiantes
    Route::prefix('v1/student')->middleware('role:student')->group(function () {
        Route::get('my-tasks', function (Request $request) {
            return app(TaskController::class)->forStudent($request->user()->id);
        });
        Route::get('my-scores', function (Request $request) {
            return app(StudentScoreController::class)->byStudent($request->user()->id);
        });
        Route::post('submit-task', [TaskSubmissionController::class, 'store']);
    });

    // Rutas para representantes
    Route::prefix('v1/guardian')->middleware('role:guardian')->group(function () {
        Route::get('my-students', function (Request $request) {
            return app(StudentGuardianController::class)->studentsByGuardian($request->user()->id);
        });
        Route::get('student/{studentId}/info', [StudentGuardianController::class, 'studentInfo']);
        Route::get('student/{studentId}/scores', function ($studentId) {
            // HIGH-04: Verificar que el guardian tiene relación con el estudiante
            $hasAccess = \App\Models\StudentGuardian::where('guardian_id', auth()->id())
                ->where('student_id', $studentId)
                ->where('status', true)
                ->exists();
            if (! $hasAccess) {
                return response()->json(['message' => 'No tienes acceso a este estudiante'], 403);
            }

            return app(TaskSubmissionController::class)->index(request()->merge(['student_id' => $studentId, 'status' => 'graded']));
        });
        Route::get('student/{studentId}/tasks', function ($studentId) {
            // HIGH-04: Verificar que el guardian tiene relación con el estudiante
            $hasAccess = \App\Models\StudentGuardian::where('guardian_id', auth()->id())
                ->where('student_id', $studentId)
                ->where('status', true)
                ->exists();
            if (! $hasAccess) {
                return response()->json(['message' => 'No tienes acceso a este estudiante'], 403);
            }

            return app(TaskController::class)->forStudent($studentId);
        });
        Route::get('student/{studentId}/schedule', function ($studentId) {
            // HIGH-04: Verificar que el guardian tiene relación con el estudiante
            $hasAccess = \App\Models\StudentGuardian::where('guardian_id', auth()->id())
                ->where('student_id', $studentId)
                ->where('status', true)
                ->exists();
            if (! $hasAccess) {
                return response()->json(['message' => 'No tienes acceso a este estudiante'], 403);
            }

            return app(ScheduleController::class)->byStudent($studentId);
        });
        Route::get('student/{studentId}/tasks/{taskId}', function ($studentId, $taskId) {
            // HIGH-04: Verificar que el guardian tiene relación con el estudiante
            $hasAccess = \App\Models\StudentGuardian::where('guardian_id', auth()->id())
                ->where('student_id', $studentId)
                ->where('status', true)
                ->exists();
            if (! $hasAccess) {
                return response()->json(['message' => 'No tienes acceso a este estudiante'], 403);
            }

            $task = \App\Models\Task::with([
                'subjectAssignment.subject',
                'subjectAssignment.section.grade.educationLevel',
                'subjectAssignment.teacher',
                'term',
            ])->find($taskId);

            if (! $task) {
                return response()->json(['message' => 'Tarea no encontrada'], 404);
            }

            $submission = \App\Models\TaskSubmission::with(['gradedBy'])
                ->where('task_id', $taskId)
                ->where('student_id', $studentId)
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'task' => $task,
                    'submission' => $submission,
                ],
                'message' => 'Tarea obtenida exitosamente',
            ]);
        });
        Route::get('terms', function () {
            return app(\App\Http\Controllers\Api\V1\Academic\TermController::class)->index(request());
        });
    });

    // Rutas de asistencia (para profesores y administradores)
    Route::prefix('v1/attendance')->middleware('role:admin|director|coordinator|teacher')->group(function () {
        Route::get('section/{section}', [AttendanceController::class, 'getSectionAttendance']);
        Route::get('section/{section}/report', [AttendanceController::class, 'getSectionReport']);
        Route::get('section/{section}/history', [AttendanceController::class, 'getHistory']);
        Route::get('student/{studentId}/stats', [AttendanceController::class, 'getStudentStats']);
    });

    // Rutas para coordinadores
    Route::prefix('v1/coordinator')->middleware('role:admin|director|coordinator')->group(function () {
        Route::get('teachers', [\App\Http\Controllers\Api\V1\CoordinatorController::class, 'teachers']);
        Route::get('teachers/{id}', [\App\Http\Controllers\Api\V1\CoordinatorController::class, 'teacherShow']);
        Route::get('tasks-overview', [\App\Http\Controllers\Api\V1\CoordinatorController::class, 'tasksOverview']);
        Route::get('scores-overview', [\App\Http\Controllers\Api\V1\CoordinatorController::class, 'scoresOverview']);
    });
});
