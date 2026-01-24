<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\SubjectAssignment;
use App\Models\TaskSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    /**
     * Listar todas las tareas
     */
    public function index(Request $request): JsonResponse
    {
        $query = Task::with(['subjectAssignment.subject', 'subjectAssignment.section.grade', 'subjectAssignment.teacher', 'term']);

        // Filtrar por asignación de materia
        if ($request->has('subject_assignment_id')) {
            $query->where('subject_assignment_id', $request->subject_assignment_id);
        }

        // Filtrar por lapso
        if ($request->has('term_id')) {
            $query->where('term_id', $request->term_id);
        }

        // Filtrar por tipo
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filtrar solo publicadas
        if ($request->has('published') && $request->published) {
            $query->where('is_published', true);
        }

        // Filtrar por profesor (a través de subject_assignment)
        if ($request->has('teacher_id')) {
            $query->whereHas('subjectAssignment', function ($q) use ($request) {
                $q->where('teacher_id', $request->teacher_id);
            });
        }

        // Paginación
        $perPage = $request->get('per_page', 15);
        $tasks = $query->orderBy('due_date', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $tasks->items(),
            'pagination' => [
                'current_page' => $tasks->currentPage(),
                'from' => $tasks->firstItem(),
                'last_page' => $tasks->lastPage(),
                'per_page' => $tasks->perPage(),
                'to' => $tasks->lastItem(),
                'total' => $tasks->total(),
            ],
            'message' => 'Tareas obtenidas exitosamente',
        ]);
    }

    /**
     * Crear una nueva tarea
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subject_assignment_id' => 'required|exists:subject_assignments,id',
            'term_id' => 'required|exists:terms,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'type' => 'required|in:homework,exam,quiz,project,activity',
            'max_score' => 'nullable|numeric|min:0|max:100',
            'weight' => 'nullable|numeric|min:0|max:100',
            'due_date' => 'nullable|date',
            'available_from' => 'nullable|date',
            'is_published' => 'boolean',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error de validación', $validator->errors(), 422);
        }

        // Verificar que el usuario autenticado sea el profesor de la asignación
        $assignment = SubjectAssignment::find($request->subject_assignment_id);
        if (Auth::id() !== $assignment->teacher_id && !Auth::user()->hasAnyRole(['admin', 'director', 'coordinator'])) {
            return $this->sendError(
                'No tienes permiso para crear tareas en esta asignación',
                [],
                403
            );
        }

        $task = Task::create($request->all());
        $task->load(['subjectAssignment.subject', 'subjectAssignment.section.grade', 'term']);

        return $this->sendResponse($task, 'Tarea creada exitosamente', 201);
    }

    /**
     * Mostrar una tarea específica
     */
    public function show(int $id): JsonResponse
    {
        $task = Task::with([
            'subjectAssignment.subject',
            'subjectAssignment.section.grade.educationLevel',
            'subjectAssignment.teacher',
            'term',
            'submissions.student'
        ])->find($id);

        if (is_null($task)) {
            return $this->sendError('Tarea no encontrada');
        }

        return $this->sendResponse($task, 'Tarea obtenida exitosamente');
    }

    /**
     * Actualizar una tarea
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $task = Task::find($id);

        if (is_null($task)) {
            return $this->sendError('Tarea no encontrada');
        }

        // Verificar permisos
        $assignment = $task->subjectAssignment;
        if (Auth::id() !== $assignment->teacher_id && !Auth::user()->hasAnyRole(['admin', 'director', 'coordinator'])) {
            return $this->sendError(
                'No tienes permiso para editar esta tarea',
                [],
                403
            );
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'type' => 'sometimes|in:homework,exam,quiz,project,activity',
            'max_score' => 'nullable|numeric|min:0|max:100',
            'weight' => 'nullable|numeric|min:0|max:100',
            'due_date' => 'nullable|date',
            'available_from' => 'nullable|date',
            'is_published' => 'boolean',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error de validación', $validator->errors(), 422);
        }

        $task->update($request->all());
        $task->load(['subjectAssignment.subject', 'subjectAssignment.section.grade', 'term']);

        return $this->sendResponse($task, 'Tarea actualizada exitosamente');
    }

    /**
     * Eliminar una tarea
     */
    public function destroy(int $id): JsonResponse
    {
        $task = Task::find($id);

        if (is_null($task)) {
            return $this->sendError('Tarea no encontrada');
        }

        // Verificar permisos
        $assignment = $task->subjectAssignment;
        if (Auth::id() !== $assignment->teacher_id && !Auth::user()->hasAnyRole(['admin', 'director'])) {
            return $this->sendError(
                'No tienes permiso para eliminar esta tarea',
                [],
                403
            );
        }

        // Verificar si tiene entregas
        if ($task->submissions()->exists()) {
            return $this->sendError(
                'No se puede eliminar la tarea porque tiene entregas asociadas',
                [],
                409
            );
        }

        $task->delete();

        return $this->sendResponse(null, 'Tarea eliminada exitosamente');
    }

    /**
     * Publicar/despublicar una tarea
     */
    public function togglePublish(int $id): JsonResponse
    {
        $task = Task::find($id);

        if (is_null($task)) {
            return $this->sendError('Tarea no encontrada');
        }

        // Verificar permisos
        $assignment = $task->subjectAssignment;
        if (Auth::id() !== $assignment->teacher_id && !Auth::user()->hasAnyRole(['admin', 'director', 'coordinator'])) {
            return $this->sendError(
                'No tienes permiso para modificar esta tarea',
                [],
                403
            );
        }

        $task->update(['is_published' => !$task->is_published]);

        $message = $task->is_published ? 'Tarea publicada exitosamente' : 'Tarea despublicada exitosamente';

        return $this->sendResponse($task, $message);
    }

    /**
     * Obtener tareas de un estudiante (basado en su sección)
     */
    public function forStudent(int $studentId): JsonResponse
    {
        $user = \App\Models\User::find($studentId);

        if (is_null($user) || !$user->hasRole('student')) {
            return $this->sendError('Estudiante no encontrado');
        }

        $enrollment = $user->activeEnrollment();

        if (is_null($enrollment)) {
            return $this->sendError('El estudiante no tiene una inscripción activa');
        }

        // Obtener todas las tareas activas de la sección del estudiante
        // (is_published = true O status = true para mostrar tareas aunque no estén explícitamente publicadas)
        $tasks = Task::with(['subjectAssignment.subject', 'term'])
            ->whereHas('subjectAssignment', function ($q) use ($enrollment) {
                $q->where('section_id', $enrollment->section_id)
                  ->where('status', true);
            })
            ->where('status', true)
            ->orderBy('due_date', 'asc')
            ->get();

        // Agregar estado de entrega para cada tarea
        $tasks->each(function ($task) use ($studentId) {
            $submission = $task->submissions()->where('student_id', $studentId)->first();
            $task->submission_status = $submission ? $submission->status : 'pending';
            $task->submission = $submission;
        });

        return $this->sendResponse($tasks, 'Tareas del estudiante obtenidas exitosamente');
    }
}
