<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\SubjectArea;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubjectAreaController extends Controller
{
    /**
     * Listar todas las áreas de conocimiento
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $subjectAreas = SubjectArea::all();
        return $this->sendResponse($subjectAreas, 'Áreas de conocimiento obtenidas exitosamente');
    }

    /**
     * Almacenar un nuevo área de conocimiento
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:10|unique:subject_areas,code',
            'description' => 'nullable|string',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error de validación', $validator->errors(), 422);
        }

        $subjectArea = SubjectArea::create($request->all());

        return $this->sendResponse($subjectArea, 'Área de conocimiento creada exitosamente', 201);
    }

    /**
     * Mostrar un área de conocimiento específica
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $subjectArea = SubjectArea::with('subjects')->find($id);

        if (is_null($subjectArea)) {
            return $this->sendError('Área de conocimiento no encontrada');
        }

        return $this->sendResponse($subjectArea, 'Área de conocimiento obtenida exitosamente');
    }

    /**
     * Actualizar un área de conocimiento
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $subjectArea = SubjectArea::find($id);

        if (is_null($subjectArea)) {
            return $this->sendError('Área de conocimiento no encontrada');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:10|unique:subject_areas,code,' . $id,
            'description' => 'nullable|string',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error de validación', $validator->errors(), 422);
        }

        $subjectArea->update($request->all());

        return $this->sendResponse($subjectArea, 'Área de conocimiento actualizada exitosamente');
    }

    /**
     * Eliminar un área de conocimiento
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $subjectArea = SubjectArea::find($id);

        if (is_null($subjectArea)) {
            return $this->sendError('Área de conocimiento no encontrada');
        }

        // Verificar si tiene materias asociadas
        if ($subjectArea->subjects()->count() > 0) {
            return $this->sendError(
                'No se puede eliminar el área de conocimiento porque tiene materias asociadas',
                [],
                409
            );
        }

        $subjectArea->delete();

        return $this->sendResponse(null, 'Área de conocimiento eliminada exitosamente');
    }

    /**
     * Obtener las materias de un área de conocimiento específica
     *
     * @param int $id
     * @return JsonResponse
     */
    public function subjects(int $id): JsonResponse
    {
        $subjectArea = SubjectArea::with('subjects')->find($id);

        if (is_null($subjectArea)) {
            return $this->sendError('Área de conocimiento no encontrada');
        }

        return $this->sendResponse($subjectArea->subjects, 'Materias del área de conocimiento obtenidas exitosamente');
    }
}
