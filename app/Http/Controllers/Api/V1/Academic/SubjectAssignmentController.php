<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\SubjectAssignment;
use App\Services\SubjectAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectAssignmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = app(SubjectAssignmentService::class)->queryForUser($request);
        $assignments = $query->paginate($this->perPage($request));

        return $this->sendPaginatedResponse($assignments, 'Asignaciones obtenidas exitosamente');
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', SubjectAssignment::class);

        try {
            $assignment = app(SubjectAssignmentService::class)->create($request);
        } catch (\InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }

        return $this->sendResponse($assignment, 'Asignación creada exitosamente', 201);
    }

    public function show(int $id): JsonResponse
    {
        $assignment = SubjectAssignment::with(['teacher', 'subject', 'section.grade.educationLevel', 'academicPeriod'])
            ->find($id);

        if (is_null($assignment)) {
            return $this->sendError('Asignación no encontrada');
        }

        $this->authorize('view', $assignment);

        return $this->sendResponse($assignment, 'Asignación obtenida exitosamente');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $assignment = SubjectAssignment::find($id);

        if (is_null($assignment)) {
            return $this->sendError('Asignación no encontrada');
        }

        $this->authorize('update', $assignment);

        try {
            $assignment = app(SubjectAssignmentService::class)->update($assignment, $request);
        } catch (\InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }

        return $this->sendResponse($assignment, 'Asignación actualizada exitosamente');
    }

    public function destroy(int $id): JsonResponse
    {
        $assignment = SubjectAssignment::find($id);

        if (is_null($assignment)) {
            return $this->sendError('Asignación no encontrada');
        }

        $this->authorize('delete', $assignment);

        if (! app(SubjectAssignmentService::class)->canDestroy($assignment)) {
            return $this->sendError(
                'No se puede eliminar la asignación porque tiene tareas o calificaciones asociadas',
                [],
                409
            );
        }

        $assignment->delete();

        return $this->sendResponse(null, 'Asignación eliminada exitosamente');
    }

    public function byTeacher(int $teacherId): JsonResponse
    {
        $this->authorize('viewByTeacher', [SubjectAssignment::class, $teacherId]);

        $assignments = app(SubjectAssignmentService::class)->byTeacher($teacherId);

        return $this->sendResponse($assignments, 'Asignaciones del profesor obtenidas exitosamente');
    }

    public function students(int $id): JsonResponse
    {
        $assignment = SubjectAssignment::find($id);

        if (is_null($assignment)) {
            return $this->sendError('Asignación no encontrada');
        }

        $this->authorize('viewStudents', $assignment);

        return $this->sendResponse(
            app(SubjectAssignmentService::class)->students($assignment),
            'Estudiantes de la asignación obtenidos exitosamente'
        );
    }
}
