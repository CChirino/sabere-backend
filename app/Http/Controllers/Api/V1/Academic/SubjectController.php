<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SubjectController extends Controller
{
    /**
     * Listar todas las materias
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Subject::with('subjectArea');

        // Filtrar por área de conocimiento si se proporciona
        if ($request->has('subject_area_id')) {
            $query->where('subject_area_id', $request->subject_area_id);
        }

        $subjects = $query->get();
        
        return $this->sendResponse($subjects, 'Materias obtenidas exitosamente');
    }

    /**
     * Almacenar una nueva materia
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subject_area_id' => 'required|exists:subject_areas,id',
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:subjects,code',
            'description' => 'nullable|string',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error de validación', $validator->errors(), 422);
        }

        $subject = Subject::create($request->all());
        $subject->load('subjectArea');

        return $this->sendResponse($subject, 'Materia creada exitosamente', 201);
    }

    /**
     * Mostrar una materia específica
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $subject = Subject::with(['subjectArea', 'grades'])->find($id);

        if (is_null($subject)) {
            return $this->sendError('Materia no encontrada');
        }

        return $this->sendResponse($subject, 'Materia obtenida exitosamente');
    }

    /**
     * Actualizar una materia
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $subject = Subject::find($id);

        if (is_null($subject)) {
            return $this->sendError('Materia no encontrada');
        }

        $validator = Validator::make($request->all(), [
            'subject_area_id' => 'required|exists:subject_areas,id',
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:subjects,code,' . $id,
            'description' => 'nullable|string',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error de validación', $validator->errors(), 422);
        }

        $subject->update($request->all());
        $subject->load('subjectArea');

        return $this->sendResponse($subject, 'Materia actualizada exitosamente');
    }

    /**
     * Eliminar una materia
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $subject = Subject::find($id);

        if (is_null($subject)) {
            return $this->sendError('Materia no encontrada');
        }

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
     *
     * @param Request $request
     * @param int $subjectId
     * @return JsonResponse
     */
    public function assignToGrade(Request $request, int $subjectId): JsonResponse
    {
        $subject = Subject::find($subjectId);

        if (is_null($subject)) {
            return $this->sendError('Materia no encontrada');
        }

        $validator = Validator::make($request->all(), [
            'grade_id' => 'required|exists:grades,id',
            'school_year' => 'required|string|size:9|regex:/^\d{4}-\d{4}$/',
            'hours_per_week' => 'required|integer|min:1|max:20',
            'is_optional' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error de validación', $validator->errors(), 422);
        }

        // Verificar si ya existe la asignación
        $exists = $subject->grades()
            ->where('grade_id', $request->grade_id)
            ->where('school_year', $request->school_year)
            ->exists();

        if ($exists) {
            return $this->sendError(
                'La materia ya está asignada a este grado para el año escolar especificado',
                [],
                409
            );
        }

        // Asignar la materia al grado
        $subject->grades()->attach($request->grade_id, [
            'school_year' => $request->school_year,
            'hours_per_week' => $request->hours_per_week,
            'is_optional' => $request->boolean('is_optional', false),
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
     *
     * @param int $subjectId
     * @param int $gradeId
     * @param string $schoolYear
     * @return JsonResponse
     */
    public function removeFromGrade(int $subjectId, int $gradeId, string $schoolYear): JsonResponse
    {
        $subject = Subject::find($subjectId);

        if (is_null($subject)) {
            return $this->sendError('Materia no encontrada');
        }

        // Verificar si existe la asignación
        $exists = $subject->grades()
            ->where('grade_id', $gradeId)
            ->where('school_year', $schoolYear)
            ->exists();

        if (!$exists) {
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
     *
     * @param int $id
     * @return JsonResponse
     */
    public function grades(int $id): JsonResponse
    {
        $subject = Subject::with('grades')->find($id);

        if (is_null($subject)) {
            return $this->sendError('Materia no encontrada');
        }

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
