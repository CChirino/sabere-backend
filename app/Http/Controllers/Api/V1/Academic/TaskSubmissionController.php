<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskSubmissionController extends Controller
{
    /**
     * Listar entregas de una tarea
     */
    public function index(Request $request): JsonResponse
    {
        $query = TaskSubmission::with(['task.subjectAssignment.subject', 'student', 'gradedBy']);

        $user = Auth::user();

        // Los estudiantes solo ven sus propias entregas
        if ($user->hasRole('student')) {
            $query->where('student_id', $user->id);
        }

        // Los representantes solo ven las de sus estudiantes vinculados
        if ($user->hasRole('guardian')) {
            $studentIds = $user->students()->pluck('users.id');
            $query->whereIn('student_id', $studentIds);
        }

        // Los profesores solo ven las entregas de sus asignaciones
        if ($user->hasRole('teacher')) {
            $query->whereHas('task.subjectAssignment', function ($q) use ($user) {
                $q->where('teacher_id', $user->id);
            });
        }

        // Filtrar por tarea
        if ($request->has('task_id')) {
            $query->where('task_id', $request->task_id);
        }

        // Filtrar por estudiante
        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        // Filtrar por estado
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $submissions = $query->orderBy('submitted_at', 'desc')->get();

        return $this->sendResponse($submissions, 'Entregas obtenidas exitosamente');
    }

    /**
     * Crear/enviar una entrega
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'content' => 'nullable|string',
            'file' => 'nullable|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,zip,rar,txt,odt,ods,odp,webp',
            'files' => 'nullable|array',
            'files.*' => 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,zip,rar,txt,odt,ods,odp,webp',
        ]);

        $task = Task::findOrFail($validated['task_id']);

        $this->authorize('create', [TaskSubmission::class, $task]);

        // Verificar que la tarea esté activa (status = true)
        if (! $task->status) {
            return $this->sendError('La tarea no está disponible', [], 403);
        }

        $studentId = Auth::id();

        // Verificar que no exista ya una entrega calificada
        $existingSubmission = TaskSubmission::where('task_id', $validated['task_id'])
            ->where('student_id', $studentId)
            ->first();

        if ($existingSubmission && $existingSubmission->status === 'graded') {
            return $this->sendError('No puedes modificar una entrega ya calificada', [], 409);
        }

        // Manejar archivos
        $filePaths = [];

        // Single file (backwards compatibility)
        if ($request->hasFile('file')) {
            $filePaths[] = $request->file('file')->store('submissions/'.$task->id, 'public');
        }

        // Multiple files
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $filePaths[] = $file->store('submissions/'.$task->id, 'public');
            }
        }

        // Merge with existing files if updating
        $existingFiles = $existingSubmission?->file_path ? json_decode($existingSubmission->file_path, true) : [];
        if (! is_array($existingFiles)) {
            $existingFiles = $existingSubmission?->file_path ? [$existingSubmission->file_path] : [];
        }

        $allFiles = array_merge($existingFiles, $filePaths);
        $filePathJson = ! empty($allFiles) ? json_encode($allFiles) : null;

        // Determinar si es entrega tardía
        $status = 'submitted';
        if ($task->due_date && now() > $task->due_date) {
            $status = 'late';
        }

        $data = [
            'task_id' => $validated['task_id'],
            'student_id' => $studentId,
            'content' => $validated['content'] ?? null,
            'file_path' => $filePathJson,
            'submitted_at' => now(),
            'status' => $status,
        ];

        if ($existingSubmission) {
            $existingSubmission->update($data);
            $submission = $existingSubmission;
        } else {
            $submission = TaskSubmission::create($data);
        }

        $submission->load(['task.subjectAssignment.subject', 'student']);

        return $this->sendResponse($submission, 'Entrega enviada exitosamente', 201);
    }

    /**
     * Mostrar una entrega específica
     */
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

    /**
     * Calificar una entrega
     */
    public function grade(Request $request, int $id): JsonResponse
    {
        $submission = TaskSubmission::find($id);

        if (is_null($submission)) {
            return $this->sendError('Entrega no encontrada');
        }

        $this->authorize('grade', $submission);

        $validated = $request->validate([
            'score' => 'required|numeric|min:0',
            'feedback' => 'nullable|string',
        ]);

        // Verificar que la nota no exceda el máximo
        $task = $submission->task;
        if ($validated['score'] > $task->max_score) {
            return $this->sendError(
                "La nota no puede exceder el máximo de {$task->max_score}",
                [],
                422
            );
        }

        $submission->update([
            'score' => $validated['score'],
            'feedback' => $validated['feedback'] ?? null,
            'graded_by' => Auth::id(),
            'graded_at' => now(),
            'status' => 'graded',
        ]);

        $submission->load(['task.subjectAssignment.subject', 'student', 'gradedBy']);

        return $this->sendResponse($submission, 'Entrega calificada exitosamente');
    }

    /**
     * Devolver una entrega para corrección
     */
    public function returnForCorrection(Request $request, int $id): JsonResponse
    {
        $submission = TaskSubmission::find($id);

        if (is_null($submission)) {
            return $this->sendError('Entrega no encontrada');
        }

        $this->authorize('returnForCorrection', $submission);

        $validated = $request->validate([
            'feedback' => 'required|string',
        ]);

        $submission->update([
            'feedback' => $validated['feedback'],
            'status' => 'returned',
        ]);

        $submission->load(['task.subjectAssignment.subject', 'student']);

        return $this->sendResponse($submission, 'Entrega devuelta para corrección');
    }

    /**
     * Obtener entregas de un estudiante
     */
    public function byStudent(int $studentId): JsonResponse
    {
        $this->authorize('viewByStudent', [TaskSubmission::class, $studentId]);

        $submissions = TaskSubmission::with(['task.subjectAssignment.subject', 'task.term'])
            ->where('student_id', $studentId)
            ->orderBy('submitted_at', 'desc')
            ->get();

        return $this->sendResponse($submissions, 'Entregas del estudiante obtenidas exitosamente');
    }

    /**
     * Obtener entregas pendientes de calificar para un profesor
     */
    public function pendingForTeacher(): JsonResponse
    {
        $teacherId = Auth::id();

        $submissions = TaskSubmission::with(['task.subjectAssignment.subject', 'student'])
            ->whereHas('task.subjectAssignment', function ($q) use ($teacherId) {
                $q->where('teacher_id', $teacherId);
            })
            ->whereIn('status', ['submitted', 'late'])
            ->orderBy('submitted_at', 'asc')
            ->get();

        return $this->sendResponse($submissions, 'Entregas pendientes obtenidas exitosamente');
    }
}
