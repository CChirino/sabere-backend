<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AcademicPeriodController extends Controller
{
    /**
     * Listar todos los períodos académicos
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = AcademicPeriod::query();

        // Filtrar por año escolar si se proporciona
        if ($request->has('school_year')) {
            $query->where('school_year', $request->school_year);
        }

        // Ordenar por fecha de inicio por defecto
        $query->orderBy('start_date');

        $periods = $query->get();
        
        return $this->sendResponse($periods, 'Períodos académicos obtenidos exitosamente');
    }

    /**
     * Almacenar un nuevo período académico
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:10',
            'school_year' => 'required|string|size:9|regex:/^\d{4}-\d{4}$/',
            'start_date' => 'required|date|before_or_equal:end_date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'boolean',
        ], [
            'school_year.regex' => 'El formato del año escolar debe ser AAAA-AAAA (ej: 2024-2025)',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error de validación', $validator->errors(), 422);
        }

        // Verificar que no exista un período con el mismo código en el mismo año escolar
        $exists = AcademicPeriod::where('code', $request->code)
            ->where('school_year', $request->school_year)
            ->exists();

        if ($exists) {
            return $this->sendError(
                'Ya existe un período con este código para el año escolar especificado',
                [],
                409
            );
        }

        $period = AcademicPeriod::create($request->all());

        return $this->sendResponse($period, 'Período académico creado exitosamente', 201);
    }

    /**
     * Mostrar un período académico específico
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $period = AcademicPeriod::find($id);

        if (is_null($period)) {
            return $this->sendError('Período académico no encontrado');
        }

        return $this->sendResponse($period, 'Período académico obtenido exitosamente');
    }

    /**
     * Actualizar un período académico
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $period = AcademicPeriod::find($id);

        if (is_null($period)) {
            return $this->sendError('Período académico no encontrado');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:10',
            'school_year' => 'required|string|size:9|regex:/^\d{4}-\d{4}$/',
            'start_date' => 'required|date|before_or_equal:end_date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'boolean',
        ], [
            'school_year.regex' => 'El formato del año escolar debe ser AAAA-AAAA (ej: 2024-2025)',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error de validación', $validator->errors(), 422);
        }

        // Verificar que no exista otro período con el mismo código en el mismo año escolar
        $exists = AcademicPeriod::where('code', $request->code)
            ->where('school_year', $request->school_year)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return $this->sendError(
                'Ya existe otro período con este código para el año escolar especificado',
                [],
                409
            );
        }

        $period->update($request->all());

        return $this->sendResponse($period, 'Período académico actualizado exitosamente');
    }

    /**
     * Eliminar un período académico
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $period = AcademicPeriod::find($id);

        if (is_null($period)) {
            return $this->sendError('Período académico no encontrado');
        }

        // Aquí podrías agregar validaciones adicionales, como verificar si hay calificaciones asociadas
        
        $period->delete();

        return $this->sendResponse(null, 'Período académico eliminado exitosamente');
    }

    /**
     * Obtener los períodos académicos por año escolar
     * 
     * @param string $schoolYear
     * @return JsonResponse
     */
    public function bySchoolYear(string $schoolYear): JsonResponse
    {
        $periods = AcademicPeriod::where('school_year', $schoolYear)
            ->orderBy('start_date')
            ->get();

        if ($periods->isEmpty()) {
            return $this->sendError(
                'No se encontraron períodos académicos para el año escolar especificado',
                [],
                404
            );
        }

        return $this->sendResponse($periods, 'Períodos académicos obtenidos exitosamente');
    }

    /**
     * Obtener el período académico actual
     * 
     * @return JsonResponse
     */
    public function current(): JsonResponse
    {
        $today = now()->format('Y-m-d');
        
        $period = AcademicPeriod::where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->where('status', true)
            ->first();

        if (is_null($period)) {
            return $this->sendError(
                'No hay un período académico activo en la fecha actual',
                [],
                404
            );
        }

        return $this->sendResponse($period, 'Período académico actual obtenido exitosamente');
    }
}
