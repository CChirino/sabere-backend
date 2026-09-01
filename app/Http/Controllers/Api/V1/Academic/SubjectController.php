<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubjectController extends Controller
{
    /**
     * Listar todas las materias
     */
    public function index(Request $request): JsonResponse
    {
        $query = Subject::with('subjectArea');

        $user = Auth::user();

        // Los estudiantes solo ven las materias de su sección
        if ($user->hasRole('student')) {
            $enrollment = $user->activeEnrollment();
            $query->whereHas('assignments', function ($q) use ($enrollment) {
                $q->where('section_id', $enrollment?->section_id);
            });
        }

        // Los representantes solo ven las materias de sus estudiantes vinculados
        if ($user->hasRole('guardian')) {
            $studentIds = $user->students()->pluck('users.id');
            $sectionIds = \App\Models\Enrollment::whereIn('student_id', $studentIds)
                ->where('status', 'active')
                ->pluck('section_id');
            $query->whereHas('assignments', function ($q) use ($sectionIds) {
                $q->whereIn('section_id', $sectionIds);
            });
        }

        // Los profesores solo ven las materias de sus asignaciones
        if ($user->hasRole('teacher')) {
            $query->whereHas('assignments', function ($q) use ($user) {
                $q->where('teacher_id', $user->id);
            });
        }

        // Filtrar por área de conocimiento si se proporciona
        if ($request->has('subject_area_id')) {
            $query->where('subject_area_id', $request->subject_area_id);
        }

        $subjects = $query->get();

        return $this->sendResponse($subjects, 'Materias obtenidas exitosamente');
    }

    /**
     * Almacenar una nueva materia
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject_area_id' => 'required|exists:subject_areas,id',
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:subjects,code',
            'description' => 'nullable|string',
            'status' => 'boolean',
        ]);

        $this->authorize('create', Subject::class);

        $subject = Subject::create($validated);
        $subject->load('subjectArea');

        return $this->sendResponse($subject, 'Materia creada exitosamente', 201);
    }

    /**
     * Mostrar una materia específica
     */
    public function show(int $id): JsonResponse
    {
        $subject = Subject::with(['subjectArea', 'grades'])->find($id);

        if (is_null($subject)) {
            return $this->sendError('Materia no encontrada');
        }

        $this->authorize('view', $subject);

        return $this->sendResponse($subject, 'Materia obtenida exitosamente');
    }

    /**
     * Actualizar una materia
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $subject = Subject::find($id);

        if (is_null($subject)) {
            return $this->sendError('Materia no encontrada');
        }

        $this->authorize('update', $subject);

        $validated = $request->validate([
            'subject_area_id' => 'required|exists:subject_areas,id',
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:subjects,code,'.$id,
            'description' => 'nullable|string',
            'status' => 'boolean',
        ]);

        $subject->update($validated);
        $subject->load('subjectArea');

        return $this->sendResponse($subject, 'Materia actualizada exitosamente');
    }

    /**
     * Eliminar una materia
     */
    public function destroy(int $id): JsonResponse
    {
        $subject = Subject::find($id);

        if (is_null($subject)) {
            return $this->sendError('Materia no encontrada');
        }

        $this->authorize('delete', $subject);

        // Verificar si está asociada a algún grado
        if ($subject->grades()->count() > 0) {
            return $this->sendError(
                'No se puede eliminar la materia porque está asociada a uno o más grados',
                [],
                409
            );
        }

        $subject->delete();

        return $this->sendResponse(null, 'Materia eliminada exitosamente');
    }

    /**
     * Asignar una materia a un grado
     */
    public function assignToGrade(Request $request, int $subjectId): JsonResponse
    {
        $subject = Subject::find($subjectId);

        if (is_null($subject)) {
            return $this->sendError('Materia no encontrada');
        }

        $this->authorize('manageGrades', Subject::class);

        $validated = $request->validate([
            'grade_id' => 'required|exists:grades,id',
            'school_year' => 'required|string|size:9|regex:/^\d{4}-\d{4}$/',
            'hours_per_week' => 'required|integer|min:1|max:20',
            'is_optional' => 'boolean',
        ]);

        // Verificar si ya existe la asignación
        $exists = $subject->grades()
            ->where('grade_id', $validated['grade_id'])
            ->where('school_year', $validated['school_year'])
            ->exists();

        if ($exists) {
            return $this->sendError(
                'La materia ya está asignada a este grado para el año escolar especificado',
                [],
                409
            );
        }

        // Asignar la materia al grado
        $subject->grades()->attach($validated['grade_id'], [
            'school_year' => $validated['school_year'],
            'hours_per_week' => $validated['hours_per_week'],
            'is_optional' => $validated['is_optional'] ?? false,
            'status' => true,
        ]);

        return $this->sendResponse(
            null,
            'Materia asignada al grado exitosamente',
            201
        );
    }

    /**
     * Desasignar una materia de un grado
     */
    public function removeFromGrade(int $subjectId, int $gradeId, string $schoolYear): JsonResponse
    {
        $subject = Subject::find($subjectId);

        if (is_null($subject)) {
            return $this->sendError('Materia no encontrada');
        }

        $this->authorize('manageGrades', Subject::class);

        // Verificar si existe la asignación
        $exists = $subject->grades()
            ->where('grade_id', $gradeId)
            ->where('school_year', $schoolYear)
            ->exists();

        if (! $exists) {
            return $this->sendError(
                'La materia no está asignada a este grado para el año escolar especificado',
                [],
                404
            );
        }

        // Eliminar la asignación
        $subject->grades()->wherePivot('grade_id', $gradeId)
            ->wherePivot('school_year', $schoolYear)
            ->detach();

        return $this->sendResponse(
            null,
            'Materia desasignada del grado exitosamente'
        );
    }

    /**
     * Obtener los grados a los que está asignada una materia
     */
    public function grades(int $id): JsonResponse
    {
        $subject = Subject::with('grades')->find($id);

        if (is_null($subject)) {
            return $this->sendError('Materia no encontrada');
        }

        $this->authorize('view', $subject);

        return $this->sendResponse(
            $subject->grades->map(function ($grade) {
                return [
                    'id' => $grade->id,
                    'name' => $grade->name,
                    'education_level' => $grade->educationLevel->name,
                    'school_year' => $grade->pivot->school_year,
                    'hours_per_week' => $grade->pivot->hours_per_week,
                    'is_optional' => $grade->pivot->is_optional,
                    'status' => $grade->pivot->status,
                ];
            }),
            'Grados de la materia obtenidos exitosamente'
        );
    }
}
