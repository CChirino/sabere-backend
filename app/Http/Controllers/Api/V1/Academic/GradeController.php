<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\EducationLevel;
use App\Models\Grade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GradeController extends Controller
{
    /**
     * Listar todos los grados
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Grade::with('educationLevel');

        // Filtrar por nivel educativo si se proporciona
        if ($request->has('education_level_id')) {
            $query->where('education_level_id', $request->education_level_id);
        }

        $grades = $query->get();
        
        return $this->sendResponse($grades, 'Grados obtenidos exitosamente');
    }

    /**
     * Almacenar un nuevo grado
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'education_level_id' => 'required|exists:education_levels,id',
            'name' => 'required|string|max:100',
            'numeric_equivalent' => 'required|integer|min:1|max:6',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error de validación', $validator->errors(), 422);
        }

        // Verificar que no exista un grado con el mismo equivalente numérico en el mismo nivel
        $exists = Grade::where('education_level_id', $request->education_level_id)
            ->where('numeric_equivalent', $request->numeric_equivalent)
            ->exists();

        if ($exists) {
            return $this->sendError('Ya existe un grado con este número en el nivel educativo seleccionado', [], 409);
        }

        $grade = Grade::create($request->all());
        $grade->load('educationLevel');

        return $this->sendResponse($grade, 'Grado creado exitosamente', 201);
    }

    /**
     * Mostrar un grado específico
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $grade = Grade::with('educationLevel')->find($id);

        if (is_null($grade)) {
            return $this->sendError('Grado no encontrado');
        }

        return $this->sendResponse($grade, 'Grado obtenido exitosamente');
    }

    /**
     * Actualizar un grado
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $grade = Grade::find($id);

        if (is_null($grade)) {
            return $this->sendError('Grado no encontrado');
        }

        $validator = Validator::make($request->all(), [
            'education_level_id' => 'required|exists:education_levels,id',
            'name' => 'required|string|max:100',
            'numeric_equivalent' => 'required|integer|min:1|max:6',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error de validación', $validator->errors(), 422);
        }

        // Verificar que no exista otro grado con el mismo equivalente numérico en el mismo nivel
        $exists = Grade::where('education_level_id', $request->education_level_id)
            ->where('numeric_equivalent', $request->numeric_equivalent)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return $this->sendError('Ya existe un grado con este número en el nivel educativo seleccionado', [], 409);
        }

        $grade->update($request->all());
        $grade->load('educationLevel');

        return $this->sendResponse($grade, 'Grado actualizado exitosamente');
    }

    /**
     * Eliminar un grado
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $grade = Grade::find($id);

        if (is_null($grade)) {
            return $this->sendError('Grado no encontrado');
        }

        // Verificar si tiene materias asociadas
        if ($grade->subjects()->count() > 0) {
            return $this->sendError(
                'No se puede eliminar el grado porque tiene materias asociadas',
                [],
                409
            );
        }

        $grade->delete();

        return $this->sendResponse(null, 'Grado eliminado exitosamente');
    }

    /**
     * Obtener las materias de un grado específico
     *
     * @param int $id
     * @return JsonResponse
     */
    public function subjects(int $id): JsonResponse
    {
        $grade = Grade::with('subjects')->find($id);

        if (is_null($grade)) {
            return $this->sendError('Grado no encontrado');
        }

        return $this->sendResponse($grade->subjects, 'Materias del grado obtenidas exitosamente');
    }
}
