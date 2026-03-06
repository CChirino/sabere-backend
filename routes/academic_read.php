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
| Academic API Read-Only Routes
|--------------------------------------------------------------------------
| These routes are accessible by all authenticated roles including students.
| They only provide GET (read) access to academic resources.
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Niveles Educativos (solo lectura)
    Route::get('education-levels', [EducationLevelController::class, 'index']);
    Route::get('education-levels/{id}', [EducationLevelController::class, 'show']);

    // Grados (solo lectura)
    Route::get('grades', [GradeController::class, 'index']);
    Route::get('grades/{id}', [GradeController::class, 'show']);
    Route::get('grades/{id}/subjects', [GradeController::class, 'subjects']);

    // Áreas de Conocimiento (solo lectura)
    Route::get('subject-areas', [SubjectAreaController::class, 'index']);
    Route::get('subject-areas/{id}', [SubjectAreaController::class, 'show']);
    Route::get('subject-areas/{id}/subjects', [SubjectAreaController::class, 'subjects']);

    // Materias (solo lectura)
    Route::get('subjects', [SubjectController::class, 'index']);
    Route::get('subjects/{id}', [SubjectController::class, 'show']);
    Route::get('subjects/{id}/grades', [SubjectController::class, 'grades']);

    // Períodos Académicos (solo lectura)
    Route::get('academic-periods', [AcademicPeriodController::class, 'index']);
    Route::get('academic-periods/{id}', [AcademicPeriodController::class, 'show']);
    Route::get('academic-periods/by-year/{schoolYear}', [AcademicPeriodController::class, 'bySchoolYear']);
    Route::get('academic-periods/current', [AcademicPeriodController::class, 'current']);

    // Secciones (solo lectura)
    Route::get('sections', [SectionController::class, 'index']);
    Route::get('sections/{id}', [SectionController::class, 'show']);
    Route::get('sections/{id}/students', [SectionController::class, 'students']);
    Route::get('sections/{id}/subjects', [SectionController::class, 'subjects']);

    // Lapsos (solo lectura)
    Route::get('terms', [TermController::class, 'index']);
    Route::get('terms/{id}', [TermController::class, 'show']);
    Route::get('terms/current', [TermController::class, 'current']);
    Route::get('terms/by-period/{academicPeriodId}', [TermController::class, 'byAcademicPeriod']);

    // Inscripciones (solo lectura)
    Route::get('enrollments', [EnrollmentController::class, 'index']);
    Route::get('enrollments/{id}', [EnrollmentController::class, 'show']);
    Route::get('enrollments/by-student/{studentId}', [EnrollmentController::class, 'byStudent']);

    // Asignaciones de Materias (solo lectura)
    Route::get('subject-assignments', [SubjectAssignmentController::class, 'index']);
    Route::get('subject-assignments/{id}', [SubjectAssignmentController::class, 'show']);
    Route::get('subject-assignments/by-teacher/{teacherId}', [SubjectAssignmentController::class, 'byTeacher']);
    Route::get('subject-assignments/{id}/students', [SubjectAssignmentController::class, 'students']);

    // Tareas (solo lectura)
    Route::get('tasks', [TaskController::class, 'index']);
    Route::get('tasks/{id}', [TaskController::class, 'show']);
    Route::get('tasks/for-student/{studentId}', [TaskController::class, 'forStudent']);

    // Entregas de Tareas (solo lectura)
    Route::get('task-submissions', [TaskSubmissionController::class, 'index']);
    Route::get('task-submissions/{id}', [TaskSubmissionController::class, 'show']);
    Route::get('task-submissions/by-student/{studentId}', [TaskSubmissionController::class, 'byStudent']);
    Route::get('task-submissions/pending-for-teacher', [TaskSubmissionController::class, 'pendingForTeacher']);

    // Calificaciones (solo lectura)
    Route::get('student-scores', [StudentScoreController::class, 'index']);
    Route::get('student-scores/{id}', [StudentScoreController::class, 'show']);
    Route::get('student-scores/report-card/{studentId}/{termId}', [StudentScoreController::class, 'reportCard']);
    Route::get('student-scores/by-student/{studentId}', [StudentScoreController::class, 'byStudent']);

    // Notas Manuales (solo lectura)
    Route::get('manual-scores', [ManualScoreController::class, 'index']);
    Route::get('manual-scores/{id}', [ManualScoreController::class, 'show']);

    // Representantes-Estudiantes (solo lectura)
    Route::get('student-guardians', [StudentGuardianController::class, 'index']);
    Route::get('student-guardians/{id}', [StudentGuardianController::class, 'show']);
    Route::get('student-guardians/students-by-guardian/{guardianId}', [StudentGuardianController::class, 'studentsByGuardian']);
    Route::get('student-guardians/guardians-by-student/{studentId}', [StudentGuardianController::class, 'guardiansByStudent']);
    Route::get('student-guardians/student-info/{studentId}', [StudentGuardianController::class, 'studentInfo']);

    // Horarios (solo lectura)
    Route::get('schedules', [ScheduleController::class, 'index']);
    Route::get('schedules/{id}', [ScheduleController::class, 'show']);
    Route::get('schedules/by-section/{sectionId}', [ScheduleController::class, 'bySection']);
    Route::get('schedules/by-teacher/{teacherId}', [ScheduleController::class, 'byTeacher']);
    Route::get('schedules/by-student/{studentId}', [ScheduleController::class, 'byStudent']);
    Route::get('schedules/today/section/{sectionId}', [ScheduleController::class, 'todayBySection']);

    // Eventos (solo lectura)
    Route::get('events', [EventController::class, 'index']);
    Route::get('events/{id}', [EventController::class, 'show']);
    Route::get('events-upcoming', [EventController::class, 'upcoming']);
});
