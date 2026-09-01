<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\StudentGuardian;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentGuardianController extends Controller
{
    /**
     * Listar relaciones representante-estudiante
     */
    public function index(Request $request): JsonResponse
    {
        $query = StudentGuardian::with(['guardian', 'student']);

        $user = Auth::user();

        // Los estudiantes solo ven sus propios representantes
        if ($user->hasRole('student')) {
            $query->where('student_id', $user->id);
        }

        // Los representantes solo ven sus propias relaciones
        if ($user->hasRole('guardian')) {
            $query->where('guardian_id', $user->id);
        }

        // Los profesores solo ven relaciones de estudiantes en sus secciones
        if ($user->hasRole('teacher')) {
            $sectionIds = $user->subjectAssignments()->pluck('section_id');
            $studentIds = \App\Models\Enrollment::whereIn('section_id', $sectionIds)
                ->where('status', 'active')
                ->pluck('student_id');
            $query->whereIn('student_id', $studentIds);
        }

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
        $validated = $request->validate([
            'guardian_id' => 'required|exists:users,id',
            'student_id' => 'required|exists:users,id',
            'relationship' => 'required|in:father,mother,guardian,grandparent,sibling,other',
            'is_primary' => 'boolean',
            'can_pickup' => 'boolean',
            'emergency_contact' => 'boolean',
            'phone' => 'nullable|string|max:20',
            'status' => 'boolean',
        ]);

        $this->authorize('create', StudentGuardian::class);

        // Verificar que el guardian tenga rol de representante
        $guardian = User::find($validated['guardian_id']);
        if (! $guardian->hasRole('guardian')) {
            return $this->sendError('El usuario seleccionado no tiene rol de representante', [], 422);
        }

        // Verificar que el student tenga rol de estudiante
        $student = User::find($validated['student_id']);
        if (! $student->hasRole('student')) {
            return $this->sendError('El usuario seleccionado no tiene rol de estudiante', [], 422);
        }

        // Verificar que no exista ya la relación
        $exists = StudentGuardian::where('guardian_id', $validated['guardian_id'])
            ->where('student_id', $validated['student_id'])
            ->exists();

        if ($exists) {
            return $this->sendError(
                'Ya existe una relación entre este representante y estudiante',
                [],
                409
            );
        }

        // Si es representante principal, quitar el flag de otros
        if ($validated['is_primary'] ?? false) {
            StudentGuardian::where('student_id', $validated['student_id'])
                ->update(['is_primary' => false]);
        }

        $relation = StudentGuardian::create([
            'guardian_id' => $validated['guardian_id'],
            'student_id' => $validated['student_id'],
            'relationship' => $validated['relationship'],
            'is_primary' => $validated['is_primary'] ?? false,
            'can_pickup' => $validated['can_pickup'] ?? false,
            'emergency_contact' => $validated['emergency_contact'] ?? false,
            'phone' => $validated['phone'] ?? null,
            'status' => $validated['status'] ?? true,
        ]);
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

        $this->authorize('view', $relation);

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

        $this->authorize('update', $relation);

        $validated = $request->validate([
            'relationship' => 'sometimes|in:father,mother,guardian,grandparent,sibling,other',
            'is_primary' => 'boolean',
            'can_pickup' => 'boolean',
            'emergency_contact' => 'boolean',
            'phone' => 'nullable|string|max:20',
            'status' => 'boolean',
        ]);

        // Si se marca como principal, quitar el flag de otros
        if (isset($validated['is_primary']) && $validated['is_primary']) {
            StudentGuardian::where('student_id', $relation->student_id)
                ->where('id', '!=', $id)
                ->update(['is_primary' => false]);
        }

        $relation->update([
            'relationship' => $validated['relationship'] ?? $relation->relationship,
            'is_primary' => $validated['is_primary'] ?? $relation->is_primary,
            'can_pickup' => $validated['can_pickup'] ?? $relation->can_pickup,
            'emergency_contact' => $validated['emergency_contact'] ?? $relation->emergency_contact,
            'phone' => array_key_exists('phone', $validated) ? $validated['phone'] : $relation->phone,
            'status' => $validated['status'] ?? $relation->status,
        ]);
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

        $this->authorize('delete', $relation);

        $relation->delete();

        return $this->sendResponse(null, 'Relación eliminada exitosamente');
    }

    /**
     * Obtener estudiantes de un representante
     */
    public function studentsByGuardian(int $guardianId): JsonResponse
    {
        $this->authorize('viewStudentsByGuardian', [StudentGuardian::class, $guardianId]);

        $guardian = User::find($guardianId);

        if (is_null($guardian) || ! $guardian->hasRole('guardian')) {
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
        $this->authorize('viewGuardiansByStudent', [StudentGuardian::class, $studentId]);

        $student = User::find($studentId);

        if (is_null($student) || ! $student->hasRole('student')) {
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
        $this->authorize('viewStudentInfo', [StudentGuardian::class, $studentId]);

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
            },
        ])->find($studentId);

        if (is_null($student)) {
            return $this->sendError('Estudiante no encontrado');
        }

        return $this->sendResponse($student, 'Información del estudiante obtenida exitosamente');
    }
}
