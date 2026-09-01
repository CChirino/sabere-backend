<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubjectAssignmentController extends Controller
{
    /**
     * Listar todas las asignaciones de materias
     */
    public function index(Request $request): JsonResponse
    {
        $query = SubjectAssignment::with(['teacher', 'subject', 'section.grade', 'academicPeriod']);

        $user = Auth::user();

        // Los estudiantes solo ven las asignaciones de su sección
        if ($user->hasRole('student')) {
            $enrollment = $user->activeEnrollment();
            $query->where('section_id', $enrollment?->section_id);
        }

        // Los representantes solo ven las asignaciones de sus estudiantes vinculados
        if ($user->hasRole('guardian')) {
            $studentIds = $user->students()->pluck('users.id');
            $sectionIds = \App\Models\Enrollment::whereIn('student_id', $studentIds)
                ->where('status', 'active')
                ->pluck('section_id');
            $query->whereIn('section_id', $sectionIds);
        }

        // Los profesores solo ven sus propias asignaciones
        if ($user->hasRole('teacher')) {
            $query->where('teacher_id', $user->id);
        }

        // Filtrar por período académico
        if ($request->has('academic_period_id')) {
            $query->where('academic_period_id', $request->academic_period_id);
        }

        // Filtrar por profesor
        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        // Filtrar por sección
        if ($request->has('section_id')) {
            $query->where('section_id', $request->section_id);
        }

        // Filtrar por materia
        if ($request->has('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        $assignments = $query->get();

        return $this->sendResponse($assignments, 'Asignaciones obtenidas exitosamente');
    }

    /**
     * Crear una nueva asignación de materia
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'subject_id' => 'required|exists:subjects,id',
            'section_id' => 'required|exists:sections,id',
            'academic_period_id' => 'required|exists:academic_periods,id',
            'status' => 'boolean',
        ]);

        $this->authorize('create', SubjectAssignment::class);

        // Verificar que el usuario tenga rol de profesor
        $teacher = User::find($validated['teacher_id']);
        if (! $teacher->hasRole('teacher')) {
            return $this->sendError(
                'El usuario seleccionado no tiene rol de profesor',
                [],
                422
            );
        }

        // Verificar que no exista la misma asignación
        $exists = SubjectAssignment::where('subject_id', $validated['subject_id'])
            ->where('section_id', $validated['section_id'])
            ->where('academic_period_id', $validated['academic_period_id'])
            ->exists();

        if ($exists) {
            return $this->sendError(
                'Ya existe una asignación para esta materia en esta sección y período',
                [],
                409
            );
        }

        $assignment = SubjectAssignment::create($validated);
        $assignment->load(['teacher', 'subject', 'section.grade', 'academicPeriod']);

        return $this->sendResponse($assignment, 'Asignación creada exitosamente', 201);
    }

    /**
     * Mostrar una asignación específica
     */
    public function show(int $id): JsonResponse
    {
        $assignment = SubjectAssignment::with(['teacher', 'subject', 'section.grade.educationLevel', 'academicPeriod'])
            ->find($id);

        if (is_null($assignment)) {
            return $this->sendError('Asignación no encontrada');
        }

        $this->authorize('view', $assignment);

        return $this->sendResponse($assignment, 'Asignación obtenida exitosamente');
    }

    /**
     * Actualizar una asignación
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $assignment = SubjectAssignment::find($id);

        if (is_null($assignment)) {
            return $this->sendError('Asignación no encontrada');
        }

        $this->authorize('update', $assignment);

        $validated = $request->validate([
            'teacher_id' => 'sometimes|exists:users,id',
            'status' => 'boolean',
        ]);

        // Si se cambia el profesor, verificar que tenga rol de profesor
        if (isset($validated['teacher_id'])) {
            $teacher = User::find($validated['teacher_id']);
            if (! $teacher->hasRole('teacher')) {
                return $this->sendError(
                    'El usuario seleccionado no tiene rol de profesor',
                    [],
                    422
                );
            }
        }

        $assignment->update($validated);
        $assignment->load(['teacher', 'subject', 'section.grade', 'academicPeriod']);

        return $this->sendResponse($assignment, 'Asignación actualizada exitosamente');
    }

    /**
     * Eliminar una asignación
     */
    public function destroy(int $id): JsonResponse
    {
        $assignment = SubjectAssignment::find($id);

        if (is_null($assignment)) {
            return $this->sendError('Asignación no encontrada');
        }

        $this->authorize('delete', $assignment);

        // Verificar si tiene tareas o calificaciones
        if ($assignment->tasks()->exists() || $assignment->studentScores()->exists()) {
            return $this->sendError(
                'No se puede eliminar la asignación porque tiene tareas o calificaciones asociadas',
                [],
                409
            );
        }

        $assignment->delete();

        return $this->sendResponse(null, 'Asignación eliminada exitosamente');
    }

    /**
     * Obtener asignaciones de un profesor
     */
    public function byTeacher(int $teacherId): JsonResponse
    {
        $this->authorize('viewByTeacher', [SubjectAssignment::class, $teacherId]);

        $assignments = SubjectAssignment::with(['subject', 'section.grade.educationLevel', 'academicPeriod'])
            ->where('teacher_id', $teacherId)
            ->where('status', true)
            ->get();

        return $this->sendResponse($assignments, 'Asignaciones del profesor obtenidas exitosamente');
    }

    /**
     * Obtener estudiantes de una asignación (materia-sección)
     */
    public function students(int $id): JsonResponse
    {
        $assignment = SubjectAssignment::find($id);

        if (is_null($assignment)) {
            return $this->sendError('Asignación no encontrada');
        }

        $this->authorize('viewStudents', $assignment);

        $students = $assignment->section->enrollments()
            ->with('student')
            ->where('status', 'active')
            ->get()
            ->pluck('student');

        return $this->sendResponse($students, 'Estudiantes de la asignación obtenidos exitosamente');
    }
}
