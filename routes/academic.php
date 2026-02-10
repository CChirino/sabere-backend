<?php

use App\Http\Controllers\Api\V1\Academic\AcademicPeriodController;
use App\Http\Controllers\Api\V1\Academic\EducationLevelController;
use App\Http\Controllers\Api\V1\Academic\GradeController;
use App\Http\Controllers\Api\V1\Academic\SubjectAreaController;
use App\Http\Controllers\Api\V1\Academic\SubjectController;
use App\Http\Controllers\Api\V1\Academic\SectionController;
use App\Http\Controllers\Api\V1\Academic\TermController;
use App\Http\Controllers\Api\V1\Academic\EnrollmentController;
use App\Http\Controllers\Api\V1\Academic\SubjectAssignmentController;
use App\Http\Controllers\Api\V1\Academic\TaskController;
use App\Http\Controllers\Api\V1\Academic\TaskSubmissionController;
use App\Http\Controllers\Api\V1\Academic\StudentScoreController;
use App\Http\Controllers\Api\V1\Academic\ManualScoreController;
use App\Http\Controllers\Api\V1\Academic\StudentGuardianController;
use App\Http\Controllers\Api\V1\Academic\ScheduleController;
use App\Http\Controllers\Api\V1\Academic\EventController;
use Illuminate\Support\Facades\Route;

// Agrupar rutas bajo el prefijo v1
Route::prefix('v1')->group(function () {
    // Rutas para Niveles Educativos
    Route::apiResource('education-levels', EducationLevelController::class)
        ->except(['create', 'edit']);

    // Rutas para Grados
    Route::apiResource('grades', GradeController::class)
        ->except(['create', 'edit']);
    Route::get('grades/{id}/subjects', [GradeController::class, 'subjects']);

    // Rutas para Áreas de Conocimiento
    Route::apiResource('subject-areas', SubjectAreaController::class)
        ->except(['create', 'edit']);
    Route::get('subject-areas/{id}/subjects', [SubjectAreaController::class, 'subjects']);

    // Rutas para Materias
    Route::apiResource('subjects', SubjectController::class)
        ->except(['create', 'edit']);
    Route::post('subjects/{subject}/assign-grade', [SubjectController::class, 'assignToGrade']);
    Route::delete('subjects/{subject}/grades/{grade}/{schoolYear}', [SubjectController::class, 'removeFromGrade']);
    Route::get('subjects/{id}/grades', [SubjectController::class, 'grades']);

    // Rutas para Períodos Académicos
    Route::apiResource('academic-periods', AcademicPeriodController::class)
        ->except(['create', 'edit']);
    Route::get('academic-periods/by-year/{schoolYear}', [AcademicPeriodController::class, 'bySchoolYear']);
    Route::get('academic-periods/current', [AcademicPeriodController::class, 'current']);

    // Rutas para Secciones
    Route::apiResource('sections', SectionController::class)
        ->except(['create', 'edit']);
    Route::get('sections/{id}/students', [SectionController::class, 'students']);
    Route::get('sections/{id}/subjects', [SectionController::class, 'subjects']);

    // Rutas para Lapsos
    Route::apiResource('terms', TermController::class)
        ->except(['create', 'edit']);
    Route::get('terms/current', [TermController::class, 'current']);
    Route::get('terms/by-period/{academicPeriodId}', [TermController::class, 'byAcademicPeriod']);

    // Rutas para Inscripciones
    Route::apiResource('enrollments', EnrollmentController::class)
        ->except(['create', 'edit']);
    Route::get('enrollments/by-student/{studentId}', [EnrollmentController::class, 'byStudent']);
    Route::post('enrollments/{id}/transfer', [EnrollmentController::class, 'transfer']);

    // Rutas para Asignaciones de Materias (Profesor-Materia-Sección)
    Route::apiResource('subject-assignments', SubjectAssignmentController::class)
        ->except(['create', 'edit']);
    Route::get('subject-assignments/by-teacher/{teacherId}', [SubjectAssignmentController::class, 'byTeacher']);
    Route::get('subject-assignments/{id}/students', [SubjectAssignmentController::class, 'students']);

    // Rutas para Tareas
    Route::apiResource('tasks', TaskController::class)
        ->except(['create', 'edit']);
    Route::post('tasks/{id}/toggle-publish', [TaskController::class, 'togglePublish']);
    Route::get('tasks/for-student/{studentId}', [TaskController::class, 'forStudent']);

    // Rutas para Entregas de Tareas
    Route::apiResource('task-submissions', TaskSubmissionController::class)
        ->except(['create', 'edit', 'update', 'destroy']);
    Route::post('task-submissions', [TaskSubmissionController::class, 'store']);
    Route::post('task-submissions/{id}/grade', [TaskSubmissionController::class, 'grade']);
    Route::post('task-submissions/{id}/return', [TaskSubmissionController::class, 'returnForCorrection']);
    Route::get('task-submissions/by-student/{studentId}', [TaskSubmissionController::class, 'byStudent']);
    Route::get('task-submissions/pending-for-teacher', [TaskSubmissionController::class, 'pendingForTeacher']);

    // Rutas para Calificaciones
    Route::apiResource('student-scores', StudentScoreController::class)
        ->except(['create', 'edit']);
    Route::get('student-scores/report-card/{studentId}/{termId}', [StudentScoreController::class, 'reportCard']);
    Route::get('student-scores/by-student/{studentId}', [StudentScoreController::class, 'byStudent']);
    Route::post('student-scores/bulk', [StudentScoreController::class, 'bulkStore']);

    // Rutas para Notas Manuales
    Route::apiResource('manual-scores', ManualScoreController::class)
        ->except(['create', 'edit']);
    Route::post('manual-scores/bulk', [ManualScoreController::class, 'storeBulk']);

    // Rutas para Representantes-Estudiantes
    Route::apiResource('student-guardians', StudentGuardianController::class)
        ->except(['create', 'edit']);
    Route::get('student-guardians/students-by-guardian/{guardianId}', [StudentGuardianController::class, 'studentsByGuardian']);
    Route::get('student-guardians/guardians-by-student/{studentId}', [StudentGuardianController::class, 'guardiansByStudent']);
    Route::get('student-guardians/student-info/{studentId}', [StudentGuardianController::class, 'studentInfo']);

    // Rutas para Horarios
    Route::apiResource('schedules', ScheduleController::class)
        ->except(['create', 'edit']);
    Route::get('schedules/by-section/{sectionId}', [ScheduleController::class, 'bySection']);
    Route::get('schedules/by-teacher/{teacherId}', [ScheduleController::class, 'byTeacher']);
    Route::get('schedules/by-student/{studentId}', [ScheduleController::class, 'byStudent']);
    Route::get('schedules/today/section/{sectionId}', [ScheduleController::class, 'todayBySection']);

    // Rutas para Eventos
    Route::apiResource('events', EventController::class)
        ->except(['create', 'edit']);
    Route::get('events-upcoming', [EventController::class, 'upcoming']);
});
