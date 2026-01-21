<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\StudentGuardian;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class StudentGuardianController extends Controller
{
    /**
     * Listar relaciones representante-estudiante
     */
    public function index(Request $request): JsonResponse
    {
        $query = StudentGuardian::with(['guardian', 'student']);

        // Filtrar por representante
        if ($request->has('guardian_id')) {
            $query->where('guardian_id', $request->guardian_id);
        }

        // Filtrar por estudiante
        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        $relations = $query->get();

        return $this->sendResponse($relations, 'Relaciones obtenidas exitosamente');
    }

    /**
     * Crear una relación representante-estudiante
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'guardian_id' => 'required|exists:users,id',
            'student_id' => 'required|exists:users,id',
            'relationship' => 'required|in:father,mother,guardian,grandparent,sibling,other',
            'is_primary' => 'boolean',
            'can_pickup' => 'boolean',
            'emergency_contact' => 'boolean',
            'phone' => 'nullable|string|max:20',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error de validación', $validator->errors(), 422);
        }

        // Verificar que el guardian tenga rol de representante
        $guardian = User::find($request->guardian_id);
        if (!$guardian->hasRole('guardian')) {
            return $this->sendError('El usuario seleccionado no tiene rol de representante', [], 422);
        }

        // Verificar que el student tenga rol de estudiante
        $student = User::find($request->student_id);
        if (!$student->hasRole('student')) {
            return $this->sendError('El usuario seleccionado no tiene rol de estudiante', [], 422);
        }

        // Verificar que no exista ya la relación
        $exists = StudentGuardian::where('guardian_id', $request->guardian_id)
            ->where('student_id', $request->student_id)
            ->exists();

        if ($exists) {
            return $this->sendError(
                'Ya existe una relación entre este representante y estudiante',
                [],
                409
            );
        }

        // Si es representante principal, quitar el flag de otros
        if ($request->is_primary) {
            StudentGuardian::where('student_id', $request->student_id)
                ->update(['is_primary' => false]);
        }

        $relation = StudentGuardian::create($request->all());
        $relation->load(['guardian', 'student']);

        return $this->sendResponse($relation, 'Relación creada exitosamente', 201);
    }

    /**
     * Mostrar una relación específica
     */
    public function show(int $id): JsonResponse
    {
        $relation = StudentGuardian::with(['guardian', 'student'])->find($id);

        if (is_null($relation)) {
            return $this->sendError('Relación no encontrada');
        }

        return $this->sendResponse($relation, 'Relación obtenida exitosamente');
    }

    /**
     * Actualizar una relación
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $relation = StudentGuardian::find($id);

        if (is_null($relation)) {
            return $this->sendError('Relación no encontrada');
        }

        $validator = Validator::make($request->all(), [
            'relationship' => 'sometimes|in:father,mother,guardian,grandparent,sibling,other',
            'is_primary' => 'boolean',
            'can_pickup' => 'boolean',
            'emergency_contact' => 'boolean',
            'phone' => 'nullable|string|max:20',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error de validación', $validator->errors(), 422);
        }

        // Si se marca como principal, quitar el flag de otros
        if ($request->has('is_primary') && $request->is_primary) {
            StudentGuardian::where('student_id', $relation->student_id)
                ->where('id', '!=', $id)
                ->update(['is_primary' => false]);
        }

        $relation->update($request->all());
        $relation->load(['guardian', 'student']);

        return $this->sendResponse($relation, 'Relación actualizada exitosamente');
    }

    /**
     * Eliminar una relación
     */
    public function destroy(int $id): JsonResponse
    {
        $relation = StudentGuardian::find($id);

        if (is_null($relation)) {
            return $this->sendError('Relación no encontrada');
        }

        $relation->delete();

        return $this->sendResponse(null, 'Relación eliminada exitosamente');
    }

    /**
     * Obtener estudiantes de un representante
     */
    public function studentsByGuardian(int $guardianId): JsonResponse
    {
        $guardian = User::find($guardianId);

        if (is_null($guardian) || !$guardian->hasRole('guardian')) {
            return $this->sendError('Representante no encontrado');
        }

        $students = $guardian->students()
            ->with(['enrollments' => function ($q) {
                $q->where('status', 'active')
                    ->with('section.grade.educationLevel');
            }])
            ->get();

        return $this->sendResponse($students, 'Estudiantes del representante obtenidos exitosamente');
    }

    /**
     * Obtener representantes de un estudiante
     */
    public function guardiansByStudent(int $studentId): JsonResponse
    {
        $student = User::find($studentId);

        if (is_null($student) || !$student->hasRole('student')) {
            return $this->sendError('Estudiante no encontrado');
        }

        $guardians = $student->guardians()->get();

        return $this->sendResponse($guardians, 'Representantes del estudiante obtenidos exitosamente');
    }

    /**
     * Obtener información completa de un estudiante para el representante
     */
    public function studentInfo(int $studentId): JsonResponse
    {
        $guardianId = Auth::id();

        // Verificar que el representante tenga acceso a este estudiante
        $hasAccess = StudentGuardian::where('guardian_id', $guardianId)
            ->where('student_id', $studentId)
            ->where('status', true)
            ->exists();

        if (!$hasAccess && !Auth::user()->hasAnyRole(['admin', 'director', 'coordinator'])) {
            return $this->sendError('No tienes acceso a la información de este estudiante', [], 403);
        }

        $student = User::with([
            'enrollments' => function ($q) {
                $q->where('status', 'active')
                    ->with(['section.grade.educationLevel', 'academicPeriod']);
            },
            'scores' => function ($q) {
                $q->with(['subjectAssignment.subject', 'term'])
                    ->orderBy('term_id');
            },
            'taskSubmissions' => function ($q) {
                $q->with(['task.subjectAssignment.subject'])
                    ->orderBy('submitted_at', 'desc')
                    ->limit(10);
            }
        ])->find($studentId);

        if (is_null($student)) {
            return $this->sendError('Estudiante no encontrado');
        }

        return $this->sendResponse($student, 'Información del estudiante obtenida exitosamente');
    }
}
