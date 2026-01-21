<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EnrollmentController extends Controller
{
    /**
     * Listar todas las inscripciones
     */
    public function index(Request $request): JsonResponse
    {
        $query = Enrollment::with(['student', 'section.grade.educationLevel', 'academicPeriod']);

        // Filtrar por período académico
        if ($request->has('academic_period_id')) {
            $query->where('academic_period_id', $request->academic_period_id);
        }

        // Filtrar por sección
        if ($request->has('section_id')) {
            $query->where('section_id', $request->section_id);
        }

        // Filtrar por estado
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filtrar por estudiante
        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        $enrollments = $query->orderBy('enrollment_date', 'desc')->get();

        return $this->sendResponse($enrollments, 'Inscripciones obtenidas exitosamente');
    }

    /**
     * Crear una nueva inscripción
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:users,id',
            'section_id' => 'required|exists:sections,id',
            'academic_period_id' => 'required|exists:academic_periods,id',
            'enrollment_date' => 'required|date',
            'status' => 'in:active,inactive,transferred,graduated,withdrawn',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error de validación', $validator->errors(), 422);
        }

        // Verificar que el usuario tenga rol de estudiante
        $student = User::find($request->student_id);
        if (!$student->hasRole('student')) {
            return $this->sendError(
                'El usuario seleccionado no tiene rol de estudiante',
                [],
                422
            );
        }

        // Verificar que no exista una inscripción activa para el mismo período
        $exists = Enrollment::where('student_id', $request->student_id)
            ->where('academic_period_id', $request->academic_period_id)
            ->exists();

        if ($exists) {
            return $this->sendError(
                'El estudiante ya tiene una inscripción para este período académico',
                [],
                409
            );
        }

        // Verificar capacidad de la sección
        $section = Section::find($request->section_id);
        if ($section->capacity) {
            $currentCount = Enrollment::where('section_id', $request->section_id)
                ->where('status', 'active')
                ->count();

            if ($currentCount >= $section->capacity) {
                return $this->sendError(
                    'La sección ha alcanzado su capacidad máxima',
                    [],
                    409
                );
            }
        }

        $enrollment = Enrollment::create($request->all());
        $enrollment->load(['student', 'section.grade.educationLevel', 'academicPeriod']);

        return $this->sendResponse($enrollment, 'Inscripción creada exitosamente', 201);
    }

    /**
     * Mostrar una inscripción específica
     */
    public function show(int $id): JsonResponse
    {
        $enrollment = Enrollment::with(['student', 'section.grade.educationLevel', 'academicPeriod'])
            ->find($id);

        if (is_null($enrollment)) {
            return $this->sendError('Inscripción no encontrada');
        }

        return $this->sendResponse($enrollment, 'Inscripción obtenida exitosamente');
    }

    /**
     * Actualizar una inscripción
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $enrollment = Enrollment::find($id);

        if (is_null($enrollment)) {
            return $this->sendError('Inscripción no encontrada');
        }

        $validator = Validator::make($request->all(), [
            'section_id' => 'sometimes|exists:sections,id',
            'status' => 'sometimes|in:active,inactive,transferred,graduated,withdrawn',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error de validación', $validator->errors(), 422);
        }

        // Si se cambia de sección, verificar capacidad
        if ($request->has('section_id') && $request->section_id != $enrollment->section_id) {
            $section = Section::find($request->section_id);
            if ($section->capacity) {
                $currentCount = Enrollment::where('section_id', $request->section_id)
                    ->where('status', 'active')
                    ->count();

                if ($currentCount >= $section->capacity) {
                    return $this->sendError(
                        'La sección destino ha alcanzado su capacidad máxima',
                        [],
                        409
                    );
                }
            }
        }

        $enrollment->update($request->all());
        $enrollment->load(['student', 'section.grade.educationLevel', 'academicPeriod']);

        return $this->sendResponse($enrollment, 'Inscripción actualizada exitosamente');
    }

    /**
     * Eliminar una inscripción
     */
    public function destroy(int $id): JsonResponse
    {
        $enrollment = Enrollment::find($id);

        if (is_null($enrollment)) {
            return $this->sendError('Inscripción no encontrada');
        }

        $enrollment->delete();

        return $this->sendResponse(null, 'Inscripción eliminada exitosamente');
    }

    /**
     * Obtener inscripciones de un estudiante
     */
    public function byStudent(int $studentId): JsonResponse
    {
        $enrollments = Enrollment::with(['section.grade.educationLevel', 'academicPeriod'])
            ->where('student_id', $studentId)
            ->orderBy('enrollment_date', 'desc')
            ->get();

        return $this->sendResponse($enrollments, 'Inscripciones del estudiante obtenidas exitosamente');
    }

    /**
     * Transferir estudiante a otra sección
     */
    public function transfer(Request $request, int $id): JsonResponse
    {
        $enrollment = Enrollment::find($id);

        if (is_null($enrollment)) {
            return $this->sendError('Inscripción no encontrada');
        }

        $validator = Validator::make($request->all(), [
            'new_section_id' => 'required|exists:sections,id',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error de validación', $validator->errors(), 422);
        }

        // Verificar capacidad de la nueva sección
        $newSection = Section::find($request->new_section_id);
        if ($newSection->capacity) {
            $currentCount = Enrollment::where('section_id', $request->new_section_id)
                ->where('status', 'active')
                ->count();

            if ($currentCount >= $newSection->capacity) {
                return $this->sendError(
                    'La sección destino ha alcanzado su capacidad máxima',
                    [],
                    409
                );
            }
        }

        $enrollment->update([
            'section_id' => $request->new_section_id,
            'notes' => $request->notes ?? $enrollment->notes,
        ]);

        $enrollment->load(['student', 'section.grade.educationLevel', 'academicPeriod']);

        return $this->sendResponse($enrollment, 'Estudiante transferido exitosamente');
    }
}
