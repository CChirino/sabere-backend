<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = app(TaskService::class)->queryForUser($request);
        $tasks = $query->orderBy('due_date', 'desc')->paginate($this->perPage($request));

        return $this->sendPaginatedResponse($tasks, 'Tareas obtenidas exitosamente');
    }

    public function store(Request $request): JsonResponse
    {
        $assignment = \App\Models\SubjectAssignment::findOrFail($request->input('subject_assignment_id'));

        $this->authorize('create', [Task::class, $assignment]);

        $task = app(TaskService::class)->create($request);

        return $this->sendResponse($task, 'Tarea creada exitosamente', 201);
    }

    public function show(int $id): JsonResponse
    {
        $task = Task::with([
            'subjectAssignment.subject',
            'subjectAssignment.section.grade.educationLevel',
            'subjectAssignment.teacher',
            'term',
            'submissions.student',
        ])->find($id);

        if (is_null($task)) {
            return $this->sendError('Tarea no encontrada');
        }

        $this->authorize('view', $task);

        return $this->sendResponse($task, 'Tarea obtenida exitosamente');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $task = Task::find($id);

        if (is_null($task)) {
            return $this->sendError('Tarea no encontrada');
        }

        $this->authorize('update', $task);

        $task = app(TaskService::class)->update($task, $request);

        return $this->sendResponse($task, 'Tarea actualizada exitosamente');
    }

    public function destroy(int $id): JsonResponse
    {
        $task = Task::find($id);

        if (is_null($task)) {
            return $this->sendError('Tarea no encontrada');
        }

        $this->authorize('delete', $task);

        if (! app(TaskService::class)->canDestroy($task)) {
            return $this->sendError(
                'No se puede eliminar la tarea porque tiene entregas asociadas',
                [],
                409
            );
        }

        $task->delete();

        return $this->sendResponse(null, 'Tarea eliminada exitosamente');
    }

    public function togglePublish(int $id): JsonResponse
    {
        $task = Task::find($id);

        if (is_null($task)) {
            return $this->sendError('Tarea no encontrada');
        }

        $this->authorize('togglePublish', $task);

        $task = app(TaskService::class)->togglePublish($task);

        $message = $task->is_published ? 'Tarea publicada exitosamente' : 'Tarea despublicada exitosamente';

        return $this->sendResponse($task, $message);
    }

    public function forStudent(int $studentId): JsonResponse
    {
        $this->authorize('viewForStudent', [Task::class, $studentId]);

        try {
            $result = app(TaskService::class)->forStudent($studentId);
        } catch (\InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), [], 404);
        }

        return $this->sendResponse($result['tasks'], $result['message']);
    }
}
