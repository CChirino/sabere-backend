<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\SocialAuthController;
use App\Http\Controllers\Api\V1\Admin\RoleController;
use App\Http\Controllers\Api\V1\Admin\UserController;
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

    // Rutas de administración (solo para super_admin y admin)
    Route::prefix('v1/admin')->middleware(['role:super_admin|admin'])->group(function () {
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

    // Rutas para docentes
    Route::prefix('v1/teacher')->middleware('role:teacher')->group(function () {
        // Aquí irán las rutas específicas para docentes
    });

    // Rutas para estudiantes
    Route::prefix('v1/student')->middleware('role:student')->group(function () {
        // Aquí irán las rutas específicas para estudiantes
    });

    // Rutas para padres
    Route::prefix('v1/parent')->middleware('role:parent')->group(function () {
        // Aquí irán las rutas específicas para padres
    });
});