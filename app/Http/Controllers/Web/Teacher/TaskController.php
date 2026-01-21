<?php

namespace App\Http\Controllers\Web\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\SubjectAssignment;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Store a newly created task.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_assignment_id' => ['required', 'exists:subject_assignments,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'type' => ['required', 'in:homework,exam,quiz,project,activity'],
            'max_score' => ['required', 'numeric', 'min:1', 'max:100'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'due_date' => ['nullable', 'date'],
            'available_from' => ['nullable', 'date'],
            'is_published' => ['boolean'],
        ]);

        // Verify the teacher owns this assignment
        $assignment = SubjectAssignment::find($validated['subject_assignment_id']);
        if ($assignment->teacher_id !== auth()->id()) {
            return back()->withErrors(['error' => 'No tienes permiso para crear tareas en esta materia.']);
        }

        Task::create($validated);

        return redirect()->route('teacher.tasks.index')->with('success', 'Tarea creada correctamente.');
    }

    /**
     * Update the specified task.
     */
    public function update(Request $request, Task $task)
    {
        // Verify the teacher owns this task
        if ($task->subjectAssignment->teacher_id !== auth()->id()) {
            return back()->withErrors(['error' => 'No tienes permiso para editar esta tarea.']);
        }

        $validated = $request->validate([
            'subject_assignment_id' => ['required', 'exists:subject_assignments,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'type' => ['required', 'in:homework,exam,quiz,project,activity'],
            'max_score' => ['required', 'numeric', 'min:1', 'max:100'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'due_date' => ['nullable', 'date'],
            'available_from' => ['nullable', 'date'],
            'is_published' => ['boolean'],
        ]);

        $task->update($validated);

        return back()->with('success', 'Tarea actualizada correctamente.');
    }

    /**
     * Toggle publish status.
     */
    public function togglePublish(Task $task)
    {
        // Verify the teacher owns this task
        if ($task->subjectAssignment->teacher_id !== auth()->id()) {
            return back()->withErrors(['error' => 'No tienes permiso para modificar esta tarea.']);
        }

        $task->update(['is_published' => !$task->is_published]);

        $message = $task->is_published ? 'Tarea publicada correctamente.' : 'Tarea despublicada correctamente.';
        return back()->with('success', $message);
    }

    /**
     * Remove the specified task.
     */
    public function destroy(Task $task)
    {
        // Verify the teacher owns this task
        if ($task->subjectAssignment->teacher_id !== auth()->id()) {
            return back()->withErrors(['error' => 'No tienes permiso para eliminar esta tarea.']);
        }

        // Check if task has submissions
        if ($task->submissions()->count() > 0) {
            return back()->withErrors(['error' => 'No se puede eliminar la tarea porque tiene entregas asociadas.']);
        }

        $task->delete();

        return redirect()->route('teacher.tasks.index')->with('success', 'Tarea eliminada correctamente.');
    }
}
