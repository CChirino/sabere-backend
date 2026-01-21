<?php

namespace App\Http\Controllers\Web\Academic;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\SubjectAssignment;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    /**
     * Store a newly created schedule.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_assignment_id' => ['required', 'exists:subject_assignments,id'],
            'day_of_week' => ['required', 'in:monday,tuesday,wednesday,thursday,friday,saturday'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'classroom' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:255'],
            'status' => ['boolean'],
        ]);

        // Check for section schedule conflict
        if (Schedule::hasConflict(
            $validated['subject_assignment_id'],
            $validated['day_of_week'],
            $validated['start_time'],
            $validated['end_time']
        )) {
            return back()->withErrors(['start_time' => 'Ya existe un horario en este día y hora para esta sección.']);
        }

        // Check for teacher schedule conflict
        $assignment = SubjectAssignment::find($validated['subject_assignment_id']);
        if (Schedule::teacherHasConflict(
            $assignment->teacher_id,
            $validated['day_of_week'],
            $validated['start_time'],
            $validated['end_time'],
            $assignment->academic_period_id
        )) {
            return back()->withErrors(['start_time' => 'El profesor ya tiene asignado otro horario en este día y hora.']);
        }

        Schedule::create($validated);

        return back()->with('success', 'Horario creado correctamente.');
    }

    /**
     * Update the specified schedule.
     */
    public function update(Request $request, Schedule $schedule)
    {
        $validated = $request->validate([
            'subject_assignment_id' => ['required', 'exists:subject_assignments,id'],
            'day_of_week' => ['required', 'in:monday,tuesday,wednesday,thursday,friday,saturday'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'classroom' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:255'],
            'status' => ['boolean'],
        ]);

        // Check for section schedule conflict (excluding current)
        if (Schedule::hasConflict(
            $validated['subject_assignment_id'],
            $validated['day_of_week'],
            $validated['start_time'],
            $validated['end_time'],
            $schedule->id
        )) {
            return back()->withErrors(['start_time' => 'Ya existe un horario en este día y hora para esta sección.']);
        }

        // Check for teacher schedule conflict (excluding current)
        $assignment = SubjectAssignment::find($validated['subject_assignment_id']);
        if (Schedule::teacherHasConflict(
            $assignment->teacher_id,
            $validated['day_of_week'],
            $validated['start_time'],
            $validated['end_time'],
            $assignment->academic_period_id,
            $schedule->id
        )) {
            return back()->withErrors(['start_time' => 'El profesor ya tiene asignado otro horario en este día y hora.']);
        }

        $schedule->update($validated);

        return back()->with('success', 'Horario actualizado correctamente.');
    }

    /**
     * Remove the specified schedule.
     */
    public function destroy(Schedule $schedule)
    {
        $schedule->delete();

        return back()->with('success', 'Horario eliminado correctamente.');
    }
}
