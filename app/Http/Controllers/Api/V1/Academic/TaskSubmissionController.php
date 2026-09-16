<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskSubmission;
use App\Services\TaskSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskSubmissionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $submissions = app(TaskSubmissionService::class)->queryForUser($request);

        return $this->sendPaginatedResponse($submissions, 'Entregas obtenidas exitosamente');
    }

    public function store(Request $request): JsonResponse
    {
        $task = Task::findOrFail($request->input('task_id'));

        $this->authorize('create', [TaskSubmission::class, $task]);

        try {
            $submission = app(TaskSubmissionService::class)->store($request);
        } catch (\InvalidArgumentException $e) {
            $code = str_contains($e->getMessage(), 'disponible') ? 403 : 409;

            return $this->sendError($e->getMessage(), [], $code);
        }

        return $this->sendResponse($submission, 'Entrega enviada exitosamente', 201);
    }

    public function show(int $id): JsonResponse
    {
        $submission = TaskSubmission::with(['task.subjectAssignment.subject', 'student', 'gradedBy'])
            ->find($id);

        if (is_null($submission)) {
            return $this->sendError('Entrega no encontrada');
        }

        $this->authorize('view', $submission);

        return $this->sendResponse($submission, 'Entrega obtenida exitosamente');
    }

    public function grade(Request $request, int $id): JsonResponse
    {
        $submission = TaskSubmission::find($id);

        if (is_null($submission)) {
            return $this->sendError('Entrega no encontrada');
        }

        $this->authorize('grade', $submission);

        try {
            $submission = app(TaskSubmissionService::class)->grade($submission, $request);
        } catch (\InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }

        return $this->sendResponse($submission, 'Entrega calificada exitosamente');
    }

    public function returnForCorrection(Request $request, int $id): JsonResponse
    {
        $submission = TaskSubmission::find($id);

        if (is_null($submission)) {
            return $this->sendError('Entrega no encontrada');
        }

        $this->authorize('returnForCorrection', $submission);

        $submission = app(TaskSubmissionService::class)->returnForCorrection($submission, $request);

        return $this->sendResponse($submission, 'Entrega devuelta para corrección');
    }

    public function byStudent(int $studentId): JsonResponse
    {
        $this->authorize('viewByStudent', [TaskSubmission::class, $studentId]);

        try {
            $submissions = app(TaskSubmissionService::class)->byStudent($studentId);
        } catch (\InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), [], 404);
        }

        return $this->sendResponse($submissions, 'Entregas del estudiante obtenidas exitosamente');
    }

    public function pendingForTeacher(): JsonResponse
    {
        $submissions = app(TaskSubmissionService::class)->pendingForTeacher();

        return $this->sendResponse($submissions, 'Entregas pendientes obtenidas exitosamente');
    }
}
