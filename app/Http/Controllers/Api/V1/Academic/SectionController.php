<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Grade;
use App\Models\AcademicPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SectionController extends Controller
{
    /**
     * Listar todas las secciones
     */
    public function index(Request $request): JsonResponse
    {
        $query = Section::with(['grade.educationLevel', 'academicPeriod'])
            ->withCount('enrollments');

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
        $validator = Validator::make($request->all(), [
            'grade_id' => 'required|exists:grades,id',
            'academic_period_id' => 'required|exists:academic_periods,id',
            'name' => 'required|string|max:10',
            'capacity' => 'nullable|integer|min:1|max:100',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error de validación', $validator->errors(), 422);
        }

        // Verificar que no exista una sección con el mismo nombre para el grado y período
        $exists = Section::where('grade_id', $request->grade_id)
            ->where('academic_period_id', $request->academic_period_id)
            ->where('name', $request->name)
            ->exists();

        if ($exists) {
            return $this->sendError(
                'Ya existe una sección con este nombre para el grado y período especificado',
                [],
                409
            );
        }

        $section = Section::create($request->all());
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

        $validator = Validator::make($request->all(), [
            'grade_id' => 'required|exists:grades,id',
            'academic_period_id' => 'required|exists:academic_periods,id',
            'name' => 'required|string|max:10',
            'capacity' => 'nullable|integer|min:1|max:100',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error de validación', $validator->errors(), 422);
        }

        // Verificar duplicados excluyendo el actual
        $exists = Section::where('grade_id', $request->grade_id)
            ->where('academic_period_id', $request->academic_period_id)
            ->where('name', $request->name)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return $this->sendError(
                'Ya existe otra sección con este nombre para el grado y período especificado',
                [],
                409
            );
        }

        $section->update($request->all());
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

        $assignments = $section->subjectAssignments()
            ->with(['subject', 'teacher'])
            ->where('status', true)
            ->get();

        return $this->sendResponse($assignments, 'Materias de la sección obtenidas exitosamente');
    }
}
