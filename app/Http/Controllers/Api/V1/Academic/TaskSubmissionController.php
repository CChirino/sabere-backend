<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\TaskSubmission;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TaskSubmissionController extends Controller
{
    /**
     * Listar entregas de una tarea
     */
    public function index(Request $request): JsonResponse
    {
        $query = TaskSubmission::with(['task.subjectAssignment.subject', 'student', 'gradedBy']);

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
        $validator = Validator::make($request->all(), [
            'task_id' => 'required|exists:tasks,id',
            'content' => 'nullable|string',
            'file' => 'nullable|file|max:10240', // 10MB max (single file)
            'files' => 'nullable|array',
            'files.*' => 'file|max:10240', // 10MB max per file
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error de validación', $validator->errors(), 422);
        }

        $task = Task::find($request->task_id);

        // Verificar que la tarea esté activa (status = true)
        if (!$task->status) {
            return $this->sendError('La tarea no está disponible', [], 403);
        }

        $studentId = Auth::id();

        // Verificar que no exista ya una entrega calificada
        $existingSubmission = TaskSubmission::where('task_id', $request->task_id)
            ->where('student_id', $studentId)
            ->first();

        if ($existingSubmission && $existingSubmission->status === 'graded') {
            return $this->sendError('No puedes modificar una entrega ya calificada', [], 409);
        }

        // Manejar archivos
        $filePaths = [];
        
        // Single file (backwards compatibility)
        if ($request->hasFile('file')) {
            $filePaths[] = $request->file('file')->store('submissions/' . $task->id, 'public');
        }
        
        // Multiple files
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $filePaths[] = $file->store('submissions/' . $task->id, 'public');
            }
        }

        // Merge with existing files if updating
        $existingFiles = $existingSubmission?->file_path ? json_decode($existingSubmission->file_path, true) : [];
        if (!is_array($existingFiles)) {
            $existingFiles = $existingSubmission?->file_path ? [$existingSubmission->file_path] : [];
        }
        
        $allFiles = array_merge($existingFiles, $filePaths);
        $filePathJson = !empty($allFiles) ? json_encode($allFiles) : null;

        // Determinar si es entrega tardía
        $status = 'submitted';
        if ($task->due_date && now() > $task->due_date) {
            $status = 'late';
        }

        $data = [
            'task_id' => $request->task_id,
            'student_id' => $studentId,
            'content' => $request->content,
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

        $validator = Validator::make($request->all(), [
            'score' => 'required|numeric|min:0',
            'feedback' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error de validación', $validator->errors(), 422);
        }

        // Verificar que la nota no exceda el máximo
        $task = $submission->task;
        if ($request->score > $task->max_score) {
            return $this->sendError(
                "La nota no puede exceder el máximo de {$task->max_score}",
                [],
                422
            );
        }

        // Verificar permisos (solo el profesor de la materia o admin)
        $assignment = $task->subjectAssignment;
        if (Auth::id() !== $assignment->teacher_id && !Auth::user()->hasAnyRole(['admin', 'director', 'coordinator'])) {
            return $this->sendError(
                'No tienes permiso para calificar esta entrega',
                [],
                403
            );
        }

        $submission->update([
            'score' => $request->score,
            'feedback' => $request->feedback,
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

        $validator = Validator::make($request->all(), [
            'feedback' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error de validación', $validator->errors(), 422);
        }

        // Verificar permisos
        $assignment = $submission->task->subjectAssignment;
        if (Auth::id() !== $assignment->teacher_id && !Auth::user()->hasAnyRole(['admin', 'director', 'coordinator'])) {
            return $this->sendError(
                'No tienes permiso para devolver esta entrega',
                [],
                403
            );
        }

        $submission->update([
            'feedback' => $request->feedback,
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
