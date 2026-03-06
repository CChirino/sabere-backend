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

/*
|--------------------------------------------------------------------------
| Academic API Write Routes (POST/PUT/DELETE)
|--------------------------------------------------------------------------
| These routes require admin, director, coordinator, or teacher roles.
| Read-only (GET) routes are in academic_read.php.
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Niveles Educativos (escritura)
    Route::post('education-levels', [EducationLevelController::class, 'store']);
    Route::put('education-levels/{id}', [EducationLevelController::class, 'update']);
    Route::delete('education-levels/{id}', [EducationLevelController::class, 'destroy']);

    // Grados (escritura)
    Route::post('grades', [GradeController::class, 'store']);
    Route::put('grades/{id}', [GradeController::class, 'update']);
    Route::delete('grades/{id}', [GradeController::class, 'destroy']);

    // Áreas de Conocimiento (escritura)
    Route::post('subject-areas', [SubjectAreaController::class, 'store']);
    Route::put('subject-areas/{id}', [SubjectAreaController::class, 'update']);
    Route::delete('subject-areas/{id}', [SubjectAreaController::class, 'destroy']);

    // Materias (escritura)
    Route::post('subjects', [SubjectController::class, 'store']);
    Route::put('subjects/{id}', [SubjectController::class, 'update']);
    Route::delete('subjects/{id}', [SubjectController::class, 'destroy']);
    Route::post('subjects/{subject}/assign-grade', [SubjectController::class, 'assignToGrade']);
    Route::delete('subjects/{subject}/grades/{grade}/{schoolYear}', [SubjectController::class, 'removeFromGrade']);

    // Períodos Académicos (escritura)
    Route::post('academic-periods', [AcademicPeriodController::class, 'store']);
    Route::put('academic-periods/{id}', [AcademicPeriodController::class, 'update']);
    Route::delete('academic-periods/{id}', [AcademicPeriodController::class, 'destroy']);

    // Secciones (escritura)
    Route::post('sections', [SectionController::class, 'store']);
    Route::put('sections/{id}', [SectionController::class, 'update']);
    Route::delete('sections/{id}', [SectionController::class, 'destroy']);

    // Lapsos (escritura)
    Route::post('terms', [TermController::class, 'store']);
    Route::put('terms/{id}', [TermController::class, 'update']);
    Route::delete('terms/{id}', [TermController::class, 'destroy']);

    // Inscripciones (escritura)
    Route::post('enrollments', [EnrollmentController::class, 'store']);
    Route::put('enrollments/{id}', [EnrollmentController::class, 'update']);
    Route::delete('enrollments/{id}', [EnrollmentController::class, 'destroy']);
    Route::post('enrollments/{id}/transfer', [EnrollmentController::class, 'transfer']);

    // Asignaciones de Materias (escritura)
    Route::post('subject-assignments', [SubjectAssignmentController::class, 'store']);
    Route::put('subject-assignments/{id}', [SubjectAssignmentController::class, 'update']);
    Route::delete('subject-assignments/{id}', [SubjectAssignmentController::class, 'destroy']);

    // Tareas (escritura)
    Route::post('tasks', [TaskController::class, 'store']);
    Route::put('tasks/{id}', [TaskController::class, 'update']);
    Route::delete('tasks/{id}', [TaskController::class, 'destroy']);
    Route::post('tasks/{id}/toggle-publish', [TaskController::class, 'togglePublish']);

    // Entregas de Tareas (escritura)
    Route::post('task-submissions', [TaskSubmissionController::class, 'store']);
    Route::post('task-submissions/{id}/grade', [TaskSubmissionController::class, 'grade']);
    Route::post('task-submissions/{id}/return', [TaskSubmissionController::class, 'returnForCorrection']);

    // Calificaciones (escritura)
    Route::post('student-scores', [StudentScoreController::class, 'store']);
    Route::put('student-scores/{id}', [StudentScoreController::class, 'update']);
    Route::delete('student-scores/{id}', [StudentScoreController::class, 'destroy']);
    Route::post('student-scores/bulk', [StudentScoreController::class, 'bulkStore']);

    // Notas Manuales (escritura)
    Route::post('manual-scores', [ManualScoreController::class, 'store']);
    Route::put('manual-scores/{id}', [ManualScoreController::class, 'update']);
    Route::delete('manual-scores/{id}', [ManualScoreController::class, 'destroy']);
    Route::post('manual-scores/bulk', [ManualScoreController::class, 'storeBulk']);

    // Representantes-Estudiantes (escritura)
    Route::post('student-guardians', [StudentGuardianController::class, 'store']);
    Route::put('student-guardians/{id}', [StudentGuardianController::class, 'update']);
    Route::delete('student-guardians/{id}', [StudentGuardianController::class, 'destroy']);

    // Horarios (escritura)
    Route::post('schedules', [ScheduleController::class, 'store']);
    Route::put('schedules/{id}', [ScheduleController::class, 'update']);
    Route::delete('schedules/{id}', [ScheduleController::class, 'destroy']);

    // Eventos (escritura)
    Route::post('events', [EventController::class, 'store']);
    Route::put('events/{id}', [EventController::class, 'update']);
    Route::delete('events/{id}', [EventController::class, 'destroy']);
});
