<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\StudentGuardian;
use App\Services\StudentGuardianService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentGuardianController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = app(StudentGuardianService::class)->queryForUser($request);
        $relations = $query->paginate($this->perPage($request));

        return $this->sendPaginatedResponse($relations, 'Relaciones obtenidas exitosamente');
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', StudentGuardian::class);

        try {
            $relation = app(StudentGuardianService::class)->create($request);
        } catch (\InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }

        return $this->sendResponse($relation, 'Relación creada exitosamente', 201);
    }

    public function show(int $id): JsonResponse
    {
        $relation = StudentGuardian::with(['guardian', 'student'])->find($id);

        if (is_null($relation)) {
            return $this->sendError('Relación no encontrada');
        }

        $this->authorize('view', $relation);

        return $this->sendResponse($relation, 'Relación obtenida exitosamente');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $relation = StudentGuardian::find($id);

        if (is_null($relation)) {
            return $this->sendError('Relación no encontrada');
        }

        $this->authorize('update', $relation);

        $relation = app(StudentGuardianService::class)->update($relation, $request);

        return $this->sendResponse($relation, 'Relación actualizada exitosamente');
    }

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

    public function studentsByGuardian(int $guardianId): JsonResponse
    {
        $this->authorize('viewStudentsByGuardian', [StudentGuardian::class, $guardianId]);

        try {
            $result = app(StudentGuardianService::class)->studentsByGuardian($guardianId);
        } catch (\InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), [], 404);
        }

        return $this->sendResponse($result['students'], $result['message']);
    }

    public function guardiansByStudent(int $studentId): JsonResponse
    {
        $this->authorize('viewGuardiansByStudent', [StudentGuardian::class, $studentId]);

        try {
            $result = app(StudentGuardianService::class)->guardiansByStudent($studentId);
        } catch (\InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), [], 404);
        }

        return $this->sendResponse($result['guardians'], $result['message']);
    }

    public function studentInfo(int $studentId): JsonResponse
    {
        $this->authorize('viewStudentInfo', [StudentGuardian::class, $studentId]);

        try {
            $student = app(StudentGuardianService::class)->studentInfo($studentId);
        } catch (\InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), [], 404);
        }

        return $this->sendResponse($student, 'Información del estudiante obtenida exitosamente');
    }
}
