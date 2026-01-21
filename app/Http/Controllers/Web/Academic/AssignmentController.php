<?php

namespace App\Http\Controllers\Web\Academic;

use App\Http\Controllers\Controller;
use App\Models\SubjectAssignment;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    /**
     * Store a newly created assignment.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'teacher_id' => ['required', 'exists:users,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'section_id' => ['required', 'exists:sections,id'],
            'academic_period_id' => ['required', 'exists:academic_periods,id'],
            'status' => ['boolean'],
        ]);

        // Check if assignment already exists
        $exists = SubjectAssignment::where('subject_id', $validated['subject_id'])
            ->where('section_id', $validated['section_id'])
            ->where('academic_period_id', $validated['academic_period_id'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['subject_id' => 'Ya existe una asignación para esta materia en esta sección y período.']);
        }

        SubjectAssignment::create($validated);

        return back()->with('success', 'Asignación creada correctamente.');
    }

    /**
     * Update the specified assignment.
     */
    public function update(Request $request, SubjectAssignment $assignment)
    {
        $validated = $request->validate([
            'teacher_id' => ['required', 'exists:users,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'section_id' => ['required', 'exists:sections,id'],
            'academic_period_id' => ['required', 'exists:academic_periods,id'],
            'status' => ['boolean'],
        ]);

        // Check if assignment already exists (excluding current)
        $exists = SubjectAssignment::where('subject_id', $validated['subject_id'])
            ->where('section_id', $validated['section_id'])
            ->where('academic_period_id', $validated['academic_period_id'])
            ->where('id', '!=', $assignment->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['subject_id' => 'Ya existe una asignación para esta materia en esta sección y período.']);
        }

        $assignment->update($validated);

        return back()->with('success', 'Asignación actualizada correctamente.');
    }

    /**
     * Remove the specified assignment.
     */
    public function destroy(SubjectAssignment $assignment)
    {
        if ($assignment->tasks()->count() > 0 || $assignment->schedules()->count() > 0) {
            return back()->with('error', 'No se puede eliminar la asignación porque tiene tareas o horarios asociados.');
        }

        $assignment->delete();

        return back()->with('success', 'Asignación eliminada correctamente.');
    }
}
