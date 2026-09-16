<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskSubmissionService
{
    public function queryForUser(Request $request): mixed
    {
        $query = TaskSubmission::with(['task.subjectAssignment.subject', 'student', 'gradedBy']);

        $user = Auth::user();

        if ($user->hasRole('student')) {
            $query->where('student_id', $user->id);
        }

        if ($user->hasRole('guardian')) {
            $studentIds = $user->students()->pluck('users.id');
            $query->whereIn('student_id', $studentIds);
        }

        if ($user->hasRole('teacher')) {
            $query->whereHas('task.subjectAssignment', function ($q) use ($user) {
                $q->where('teacher_id', $user->id);
            });
        }

        if ($request->has('task_id')) {
            $query->where('task_id', $request->task_id);
        }

        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $perPage = app(PaginationService::class)->perPage($request);

        return $query->orderBy('submitted_at', 'desc')->paginate($perPage);
    }

    public function store(Request $request): TaskSubmission
    {
        $validated = $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'content' => 'nullable|string',
            'file' => 'nullable|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,zip,rar,txt,odt,ods,odp,webp',
            'files' => 'nullable|array',
            'files.*' => 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,zip,rar,txt,odt,ods,odp,webp',
        ]);

        $task = Task::findOrFail($validated['task_id']);

        if (! $task->status) {
            throw new \InvalidArgumentException('La tarea no está disponible');
        }

        $studentId = Auth::id();

        $existingSubmission = TaskSubmission::where('task_id', $validated['task_id'])
            ->where('student_id', $studentId)
            ->first();

        if ($existingSubmission && $existingSubmission->status === 'graded') {
            throw new \InvalidArgumentException('No puedes modificar una entrega ya calificada');
        }

        $filePaths = [];

        if ($request->hasFile('file')) {
            $filePaths[] = $request->file('file')->store('submissions/'.$task->id, 'public');
        }

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $filePaths[] = $file->store('submissions/'.$task->id, 'public');
            }
        }

        $existingFiles = $existingSubmission?->file_path ? json_decode($existingSubmission->file_path, true) : [];
        if (! is_array($existingFiles)) {
            $existingFiles = $existingSubmission?->file_path ? [$existingSubmission->file_path] : [];
        }

        $allFiles = array_merge($existingFiles, $filePaths);
        $filePathJson = ! empty($allFiles) ? json_encode($allFiles) : null;

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

        return $submission;
    }

    public function grade(TaskSubmission $submission, Request $request): TaskSubmission
    {
        $validated = $request->validate([
            'score' => 'required|numeric|min:0',
            'feedback' => 'nullable|string',
        ]);

        $task = $submission->task;
        if ($validated['score'] > $task->max_score) {
            throw new \InvalidArgumentException("La nota no puede exceder el máximo de {$task->max_score}");
        }

        $submission->update([
            'score' => $validated['score'],
            'feedback' => $validated['feedback'] ?? null,
            'graded_by' => Auth::id(),
            'graded_at' => now(),
            'status' => 'graded',
        ]);

        $submission->load(['task.subjectAssignment.subject', 'student', 'gradedBy']);

        return $submission;
    }

    public function returnForCorrection(TaskSubmission $submission, Request $request): TaskSubmission
    {
        $validated = $request->validate([
            'feedback' => 'required|string',
        ]);

        $submission->update([
            'feedback' => $validated['feedback'],
            'status' => 'returned',
        ]);

        $submission->load(['task.subjectAssignment.subject', 'student']);

        return $submission;
    }

    public function byStudent(int $studentId): mixed
    {
        $student = User::find($studentId);

        if (is_null($student) || ! $student->hasRole('student')) {
            throw new \InvalidArgumentException('Estudiante no encontrado');
        }

        return TaskSubmission::with(['task.subjectAssignment.subject', 'task.term'])
            ->where('student_id', $studentId)
            ->orderBy('submitted_at', 'desc')
            ->get();
    }

    public function pendingForTeacher(): mixed
    {
        $teacherId = Auth::id();

        return TaskSubmission::with(['task.subjectAssignment.subject', 'student'])
            ->whereHas('task.subjectAssignment', function ($q) use ($teacherId) {
                $q->where('teacher_id', $teacherId);
            })
            ->whereIn('status', ['submitted', 'late'])
            ->orderBy('submitted_at', 'asc')
            ->get();
    }
}
