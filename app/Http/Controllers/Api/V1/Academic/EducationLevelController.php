<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\EducationLevel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EducationLevelController extends Controller
{
    /**
     * Listar todos los niveles educativos
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $educationLevels = EducationLevel::all();
        return $this->sendResponse($educationLevels, 'Niveles educativos obtenidos exitosamente');
    }

    /**
     * Almacenar un nuevo nivel educativo
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:10|unique:education_levels,code',
            'description' => 'nullable|string',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error de validación', $validator->errors(), 422);
        }

        $educationLevel = EducationLevel::create($request->all());

        return $this->sendResponse($educationLevel, 'Nivel educativo creado exitosamente', 201);
    }

    /**
     * Mostrar un nivel educativo específico
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $educationLevel = EducationLevel::find($id);

        if (is_null($educationLevel)) {
            return $this->sendError('Nivel educativo no encontrado');
        }

        return $this->sendResponse($educationLevel, 'Nivel educativo obtenido exitosamente');
    }

    /**
     * Actualizar un nivel educativo
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $educationLevel = EducationLevel::find($id);

        if (is_null($educationLevel)) {
            return $this->sendError('Nivel educativo no encontrado');
        }


        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:10|unique:education_levels,code,' . $id,
            'description' => 'nullable|string',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error de validación', $validator->errors(), 422);
        }


        $educationLevel->update($request->all());

        return $this->sendResponse($educationLevel, 'Nivel educativo actualizado exitosamente');
    }

    /**
     * Eliminar un nivel educativo
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $educationLevel = EducationLevel::find($id);

        if (is_null($educationLevel)) {
            return $this->sendError('Nivel educativo no encontrado');
        }

        // Verificar si tiene grados asociados
        if ($educationLevel->grades()->count() > 0) {
            return $this->sendError(
                'No se puede eliminar el nivel educativo porque tiene grados asociados',
                [],
                409
            );
        }

        $educationLevel->delete();

        return $this->sendResponse(null, 'Nivel educativo eliminado exitosamente');
    }
}
