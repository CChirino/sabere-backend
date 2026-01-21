<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\Term;
use App\Models\AcademicPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TermController extends Controller
{
    /**
     * Listar todos los lapsos
     */
    public function index(Request $request): JsonResponse
    {
        $query = Term::with('academicPeriod');

        // Filtrar por período académico
        if ($request->has('academic_period_id')) {
            $query->where('academic_period_id', $request->academic_period_id);
        }

        $terms = $query->orderBy('number')->get();

        return $this->sendResponse($terms, 'Lapsos obtenidos exitosamente');
    }

    /**
     * Crear un nuevo lapso
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'academic_period_id' => 'required|exists:academic_periods,id',
            'name' => 'required|string|max:100',
            'number' => 'required|integer|min:1|max:4',
            'start_date' => 'required|date|before_or_equal:end_date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'weight' => 'nullable|numeric|min:0|max:100',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error de validación', $validator->errors(), 422);
        }

        // Verificar que no exista un lapso con el mismo número para el período
        $exists = Term::where('academic_period_id', $request->academic_period_id)
            ->where('number', $request->number)
            ->exists();

        if ($exists) {
            return $this->sendError(
                'Ya existe un lapso con este número para el período académico especificado',
                [],
                409
            );
        }

        // Verificar que las fechas estén dentro del período académico
        $period = AcademicPeriod::find($request->academic_period_id);
        if ($request->start_date < $period->start_date || $request->end_date > $period->end_date) {
            return $this->sendError(
                'Las fechas del lapso deben estar dentro del período académico',
                [],
                422
            );
        }

        $term = Term::create($request->all());
        $term->load('academicPeriod');

        return $this->sendResponse($term, 'Lapso creado exitosamente', 201);
    }

    /**
     * Mostrar un lapso específico
     */
    public function show(int $id): JsonResponse
    {
        $term = Term::with('academicPeriod')->find($id);

        if (is_null($term)) {
            return $this->sendError('Lapso no encontrado');
        }

        return $this->sendResponse($term, 'Lapso obtenido exitosamente');
    }

    /**
     * Actualizar un lapso
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $term = Term::find($id);

        if (is_null($term)) {
            return $this->sendError('Lapso no encontrado');
        }

        $validator = Validator::make($request->all(), [
            'academic_period_id' => 'required|exists:academic_periods,id',
            'name' => 'required|string|max:100',
            'number' => 'required|integer|min:1|max:4',
            'start_date' => 'required|date|before_or_equal:end_date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'weight' => 'nullable|numeric|min:0|max:100',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error de validación', $validator->errors(), 422);
        }

        // Verificar duplicados excluyendo el actual
        $exists = Term::where('academic_period_id', $request->academic_period_id)
            ->where('number', $request->number)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return $this->sendError(
                'Ya existe otro lapso con este número para el período académico especificado',
                [],
                409
            );
        }

        $term->update($request->all());
        $term->load('academicPeriod');

        return $this->sendResponse($term, 'Lapso actualizado exitosamente');
    }

    /**
     * Eliminar un lapso
     */
    public function destroy(int $id): JsonResponse
    {
        $term = Term::find($id);

        if (is_null($term)) {
            return $this->sendError('Lapso no encontrado');
        }

        // Verificar si tiene calificaciones o tareas
        if ($term->studentScores()->exists() || $term->tasks()->exists()) {
            return $this->sendError(
                'No se puede eliminar el lapso porque tiene calificaciones o tareas asociadas',
                [],
                409
            );
        }

        $term->delete();

        return $this->sendResponse(null, 'Lapso eliminado exitosamente');
    }

    /**
     * Obtener el lapso actual
     */
    public function current(): JsonResponse
    {
        $today = now()->format('Y-m-d');

        $term = Term::with('academicPeriod')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->where('status', true)
            ->first();

        if (is_null($term)) {
            return $this->sendError(
                'No hay un lapso activo en la fecha actual',
                [],
                404
            );
        }

        return $this->sendResponse($term, 'Lapso actual obtenido exitosamente');
    }

    /**
     * Obtener lapsos por período académico
     */
    public function byAcademicPeriod(int $academicPeriodId): JsonResponse
    {
        $terms = Term::where('academic_period_id', $academicPeriodId)
            ->orderBy('number')
            ->get();

        return $this->sendResponse($terms, 'Lapsos del período obtenidos exitosamente');
    }
}
