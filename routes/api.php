<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\SocialAuthController;
use App\Http\Controllers\Api\V1\Admin\RoleController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\Academic\TaskController;
use App\Http\Controllers\Api\V1\Academic\TaskSubmissionController;
use App\Http\Controllers\Api\V1\Academic\StudentScoreController;
use App\Http\Controllers\Api\V1\Academic\StudentGuardianController;
use App\Http\Controllers\Web\Teacher\AttendanceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public routes`
    Route::post('/register', [RegisterController::class, 'register']);
    Route::post('/login', [LoginController::class, 'login']);
    
    // Google OAuth routes
    Route::get('/login/google', [SocialAuthController::class, 'redirectToGoogle']);
    Route::get('/login/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [LoginController::class, 'logout']);
        
        // User routes
        Route::get('/user', function (Request $request) {
            return response()->json([
                'user' => $request->user()
            ]);
        });
    });
});



Route::middleware(['auth:sanctum'])->group(function () {
    // Ruta para obtener el usuario autenticado
    Route::get('/user', function (Request $request) {
        return $request->user()->load('roles');
    });

    // Dashboard - accesible para todos los usuarios autenticados
    Route::get('v1/dashboard', [DashboardController::class, 'index']);

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

    // Rutas académicas - acceso según rol (incluye estudiantes para consultas)
    Route::middleware(['role:admin|director|coordinator|teacher|student'])->group(function () {
        // Incluir rutas académicas (gestión completa)
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
            return app(TaskSubmissionController::class)->index(request()->merge(['student_id' => $studentId, 'status' => 'graded']));
        });
        Route::get('student/{studentId}/tasks', function ($studentId) {
            return app(TaskController::class)->forStudent($studentId);
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