<?php

namespace App\Services;

use App\Models\SubjectAssignment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskService
{
    public function queryForUser(Request $request): Builder
    {
        $query = Task::with([
            'subjectAssignment.subject',
            'subjectAssignment.section.grade',
            'subjectAssignment.teacher',
            'term',
        ]);

        $user = Auth::user();

        if ($user->hasRole('student')) {
            $enrollment = $user->activeEnrollment();
            $query->whereHas('subjectAssignment', function ($q) use ($enrollment) {
                $q->where('section_id', $enrollment?->section_id);
            });
        }

        if ($user->hasRole('guardian')) {
            $studentIds = $user->students()->pluck('users.id');
            $sectionIds = \App\Models\Enrollment::whereIn('student_id', $studentIds)
                ->where('status', 'active')
                ->pluck('section_id');
            $query->whereHas('subjectAssignment', function ($q) use ($sectionIds) {
                $q->whereIn('section_id', $sectionIds);
            });
        }

        if ($user->hasRole('teacher')) {
            $query->whereHas('subjectAssignment', function ($q) use ($user) {
                $q->where('teacher_id', $user->id);
            });
        }

        if ($request->has('subject_assignment_id')) {
            $query->where('subject_assignment_id', $request->subject_assignment_id);
        }

        if ($request->has('term_id')) {
            $query->where('term_id', $request->term_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('published') && $request->published) {
            $query->where('is_published', true);
        }

        if ($request->has('teacher_id')) {
            $query->whereHas('subjectAssignment', function ($q) use ($request) {
                $q->where('teacher_id', $request->teacher_id);
            });
        }

        return $query;
    }

    public function create(Request $request): Task
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

        return $task;
    }

    public function update(Task $task, Request $request): Task
    {
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

        return $task;
    }

    public function togglePublish(Task $task): Task
    {
        $task->update(['is_published' => ! $task->is_published]);

        return $task;
    }

    public function forStudent(int $studentId): array
    {
        $user = User::find($studentId);

        if (is_null($user) || ! $user->hasRole('student')) {
            throw new \InvalidArgumentException('Estudiante no encontrado');
        }

        $enrollment = $user->activeEnrollment();

        if (is_null($enrollment)) {
            throw new \InvalidArgumentException('El estudiante no tiene una inscripción activa');
        }

        $tasks = Task::with(['subjectAssignment.subject', 'term'])
            ->whereHas('subjectAssignment', function ($q) use ($enrollment) {
                $q->where('section_id', $enrollment->section_id)
                    ->where('status', true);
            })
            ->where('status', true)
            ->orderBy('due_date', 'asc')
            ->get();

        $tasks->each(function ($task) use ($studentId) {
            $submission = $task->submissions()->where('student_id', $studentId)->first();
            $task->submission_status = $submission ? $submission->status : 'pending';
            $task->submission = $submission;
        });

        return [
            'tasks' => $tasks,
            'message' => 'Tareas del estudiante obtenidas exitosamente',
        ];
    }

    public function canDestroy(Task $task): bool
    {
        return ! $task->submissions()->exists();
    }
}
