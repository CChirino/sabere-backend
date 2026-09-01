<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SectionController extends Controller
{
    /**
     * Listar todas las secciones
     */
    public function index(Request $request): JsonResponse
    {
        $query = Section::with(['grade.educationLevel', 'academicPeriod'])
            ->withCount('enrollments');

        $user = Auth::user();

        // Los estudiantes solo ven su propia sección
        if ($user->hasRole('student')) {
            $enrollment = $user->activeEnrollment();
            $query->where('id', $enrollment?->section_id);
        }

        // Los representantes solo ven las secciones de sus estudiantes vinculados
        if ($user->hasRole('guardian')) {
            $studentIds = $user->students()->pluck('users.id');
            $sectionIds = \App\Models\Enrollment::whereIn('student_id', $studentIds)
                ->where('status', 'active')
                ->pluck('section_id');
            $query->whereIn('id', $sectionIds);
        }

        // Los profesores solo ven las secciones de sus asignaciones
        if ($user->hasRole('teacher')) {
            $sectionIds = $user->subjectAssignments()->pluck('section_id');
            $query->whereIn('id', $sectionIds);
        }

        // Filtrar por período académico
        if ($request->has('academic_period_id')) {
            $query->where('academic_period_id', $request->academic_period_id);
        }

        // Filtrar por grado
        if ($request->has('grade_id')) {
            $query->where('grade_id', $request->grade_id);
        }

        // Filtrar por nivel educativo
        if ($request->has('education_level_id')) {
            $query->whereHas('grade', function ($q) use ($request) {
                $q->where('education_level_id', $request->education_level_id);
            });
        }

        $sections = $query->orderBy('name')->get();

        return $this->sendResponse($sections, 'Secciones obtenidas exitosamente');
    }

    /**
     * Crear una nueva sección
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'grade_id' => 'required|exists:grades,id',
            'academic_period_id' => 'required|exists:academic_periods,id',
            'name' => 'required|string|max:10',
            'capacity' => 'nullable|integer|min:1|max:100',
            'status' => 'boolean',
        ]);

        $this->authorize('create', Section::class);

        // Verificar que no exista una sección con el mismo nombre para el grado y período
        $exists = Section::where('grade_id', $validated['grade_id'])
            ->where('academic_period_id', $validated['academic_period_id'])
            ->where('name', $validated['name'])
            ->exists();

        if ($exists) {
            return $this->sendError(
                'Ya existe una sección con este nombre para el grado y período especificado',
                [],
                409
            );
        }

        $section = Section::create($validated);
        $section->load(['grade.educationLevel', 'academicPeriod']);

        return $this->sendResponse($section, 'Sección creada exitosamente', 201);
    }

    /**
     * Mostrar una sección específica
     */
    public function show(int $id): JsonResponse
    {
        $section = Section::with(['grade.educationLevel', 'academicPeriod', 'enrollments.student'])
            ->find($id);

        if (is_null($section)) {
            return $this->sendError('Sección no encontrada');
        }

        $this->authorize('view', $section);

        return $this->sendResponse($section, 'Sección obtenida exitosamente');
    }

    /**
     * Actualizar una sección
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $section = Section::find($id);

        if (is_null($section)) {
            return $this->sendError('Sección no encontrada');
        }

        $this->authorize('update', $section);

        $validated = $request->validate([
            'grade_id' => 'required|exists:grades,id',
            'academic_period_id' => 'required|exists:academic_periods,id',
            'name' => 'required|string|max:10',
            'capacity' => 'nullable|integer|min:1|max:100',
            'status' => 'boolean',
        ]);

        // Verificar duplicados excluyendo el actual
        $exists = Section::where('grade_id', $validated['grade_id'])
            ->where('academic_period_id', $validated['academic_period_id'])
            ->where('name', $validated['name'])
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return $this->sendError(
                'Ya existe otra sección con este nombre para el grado y período especificado',
                [],
                409
            );
        }

        $section->update($validated);
        $section->load(['grade.educationLevel', 'academicPeriod']);

        return $this->sendResponse($section, 'Sección actualizada exitosamente');
    }

    /**
     * Eliminar una sección
     */
    public function destroy(int $id): JsonResponse
    {
        $section = Section::find($id);

        if (is_null($section)) {
            return $this->sendError('Sección no encontrada');
        }

        $this->authorize('delete', $section);

        // Verificar si tiene inscripciones
        if ($section->enrollments()->exists()) {
            return $this->sendError(
                'No se puede eliminar la sección porque tiene estudiantes inscritos',
                [],
                409
            );
        }

        $section->delete();

        return $this->sendResponse(null, 'Sección eliminada exitosamente');
    }

    /**
     * Obtener estudiantes de una sección
     */
    public function students(int $id): JsonResponse
    {
        $section = Section::find($id);

        if (is_null($section)) {
            return $this->sendError('Sección no encontrada');
        }

        $this->authorize('viewStudents', $section);

        $students = $section->enrollments()
            ->with('student')
            ->where('status', 'active')
            ->get()
            ->pluck('student');

        return $this->sendResponse($students, 'Estudiantes de la sección obtenidos exitosamente');
    }

    /**
     * Obtener materias asignadas a una sección
     */
    public function subjects(int $id): JsonResponse
    {
        $section = Section::find($id);

        if (is_null($section)) {
            return $this->sendError('Sección no encontrada');
        }

        $this->authorize('viewSubjects', $section);

        $assignments = $section->subjectAssignments()
            ->with(['subject', 'teacher'])
            ->where('status', true)
            ->get();

        return $this->sendResponse($assignments, 'Materias de la sección obtenidas exitosamente');
    }
}
