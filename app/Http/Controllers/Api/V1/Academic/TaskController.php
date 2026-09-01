<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\SubjectAssignment;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    /**
     * Listar todas las tareas
     */
    public function index(Request $request): JsonResponse
    {
        $query = Task::with(['subjectAssignment.subject', 'subjectAssignment.section.grade', 'subjectAssignment.teacher', 'term']);

        $user = Auth::user();

        // Los estudiantes solo ven las tareas de su sección
        if ($user->hasRole('student')) {
            $enrollment = $user->activeEnrollment();
            $query->whereHas('subjectAssignment', function ($q) use ($enrollment) {
                $q->where('section_id', $enrollment?->section_id);
            });
        }

        // Los representantes solo ven las tareas de sus estudiantes vinculados
        if ($user->hasRole('guardian')) {
            $studentIds = $user->students()->pluck('users.id');
            $sectionIds = \App\Models\Enrollment::whereIn('student_id', $studentIds)
                ->where('status', 'active')
                ->pluck('section_id');
            $query->whereHas('subjectAssignment', function ($q) use ($sectionIds) {
                $q->whereIn('section_id', $sectionIds);
            });
        }

        // Los profesores solo ven las tareas de sus asignaciones
        if ($user->hasRole('teacher')) {
            $query->whereHas('subjectAssignment', function ($q) use ($user) {
                $q->where('teacher_id', $user->id);
            });
        }

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
        $validated = $request->validate([
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

        $assignment = SubjectAssignment::findOrFail($validated['subject_assignment_id']);

        $this->authorize('create', [Task::class, $assignment]);

        $task = Task::create([
            'subject_assignment_id' => $validated['subject_assignment_id'],
            'term_id' => $validated['term_id'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'instructions' => $validated['instructions'] ?? null,
            'type' => $validated['type'],
            'max_score' => $validated['max_score'] ?? null,
            'weight' => $validated['weight'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'available_from' => $validated['available_from'] ?? null,
            'is_published' => $validated['is_published'] ?? false,
            'status' => $validated['status'] ?? true,
        ]);
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
            'submissions.student',
        ])->find($id);

        if (is_null($task)) {
            return $this->sendError('Tarea no encontrada');
        }

        $this->authorize('view', $task);

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

        $this->authorize('update', $task);

        $validated = $request->validate([
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

        $task->update($validated);
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

        $this->authorize('delete', $task);

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

        $this->authorize('togglePublish', $task);

        $task->update(['is_published' => ! $task->is_published]);

        $message = $task->is_published ? 'Tarea publicada exitosamente' : 'Tarea despublicada exitosamente';

        return $this->sendResponse($task, $message);
    }

    /**
     * Obtener tareas de un estudiante (basado en su sección)
     */
    public function forStudent(int $studentId): JsonResponse
    {
        $this->authorize('viewForStudent', [Task::class, $studentId]);

        $user = \App\Models\User::find($studentId);

        if (is_null($user) || ! $user->hasRole('student')) {
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
