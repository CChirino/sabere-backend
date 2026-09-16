<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Services\SubjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = app(SubjectService::class)->queryForUser($request);
        $subjects = $query->paginate($this->perPage($request));

        return $this->sendPaginatedResponse($subjects, 'Materias obtenidas exitosamente');
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Subject::class);

        $subject = app(SubjectService::class)->create($request);

        return $this->sendResponse($subject, 'Materia creada exitosamente', 201);
    }

    public function show(int $id): JsonResponse
    {
        $subject = Subject::with(['subjectArea', 'grades'])->find($id);

        if (is_null($subject)) {
            return $this->sendError('Materia no encontrada');
        }

        $this->authorize('view', $subject);

        return $this->sendResponse($subject, 'Materia obtenida exitosamente');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $subject = Subject::find($id);

        if (is_null($subject)) {
            return $this->sendError('Materia no encontrada');
        }

        $this->authorize('update', $subject);

        $subject = app(SubjectService::class)->update($subject, $request);

        return $this->sendResponse($subject, 'Materia actualizada exitosamente');
    }

    public function destroy(int $id): JsonResponse
    {
        $subject = Subject::find($id);

        if (is_null($subject)) {
            return $this->sendError('Materia no encontrada');
        }

        $this->authorize('delete', $subject);

        if (! app(SubjectService::class)->canDestroy($subject)) {
            return $this->sendError(
                'No se puede eliminar la materia porque está asociada a uno o más grados',
                [],
                409
            );
        }

        $subject->delete();

        return $this->sendResponse(null, 'Materia eliminada exitosamente');
    }

    public function assignToGrade(Request $request, int $subjectId): JsonResponse
    {
        $subject = Subject::find($subjectId);

        if (is_null($subject)) {
            return $this->sendError('Materia no encontrada');
        }

        $this->authorize('manageGrades', Subject::class);

        try {
            app(SubjectService::class)->assignToGrade($subject, $request);
        } catch (\InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), [], 409);
        }

        return $this->sendResponse(null, 'Materia asignada al grado exitosamente', 201);
    }

    public function removeFromGrade(int $subjectId, int $gradeId, string $schoolYear): JsonResponse
    {
        $subject = Subject::find($subjectId);

        if (is_null($subject)) {
            return $this->sendError('Materia no encontrada');
        }

        $this->authorize('manageGrades', Subject::class);

        try {
            app(SubjectService::class)->removeFromGrade($subject, $gradeId, $schoolYear);
        } catch (\InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), [], 404);
        }

        return $this->sendResponse(null, 'Materia desasignada del grado exitosamente');
    }

    public function grades(int $id): JsonResponse
    {
        $subject = Subject::find($id);

        if (is_null($subject)) {
            return $this->sendError('Materia no encontrada');
        }

        $this->authorize('view', $subject);

        $grades = app(SubjectService::class)->grades($subject);

        return $this->sendResponse($grades, 'Grados de la materia obtenidos exitosamente');
    }
}
