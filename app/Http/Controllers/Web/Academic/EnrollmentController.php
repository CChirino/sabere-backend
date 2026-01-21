<?php

namespace App\Http\Controllers\Web\Academic;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Section;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    /**
     * Store a newly created enrollment.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:users,id'],
            'section_id' => ['required', 'exists:sections,id'],
            'academic_period_id' => ['required', 'exists:academic_periods,id'],
            'enrollment_date' => ['required', 'date'],
            'status' => ['required', 'in:active,inactive,transferred,graduated,withdrawn'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        // Check if student is already enrolled in this period
        $exists = Enrollment::where('student_id', $validated['student_id'])
            ->where('academic_period_id', $validated['academic_period_id'])
            ->where('status', 'active')
            ->exists();

        if ($exists) {
            return back()->withErrors(['student_id' => 'El estudiante ya tiene una inscripción activa en este período.']);
        }

        // Check section capacity
        $section = Section::withCount('enrollments')->find($validated['section_id']);
        if ($section->capacity && $section->enrollments_count >= $section->capacity) {
            return back()->withErrors(['section_id' => 'La sección ha alcanzado su capacidad máxima.']);
        }

        Enrollment::create($validated);

        return back()->with('success', 'Inscripción creada correctamente.');
    }

    /**
     * Update the specified enrollment.
     */
    public function update(Request $request, Enrollment $enrollment)
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:users,id'],
            'section_id' => ['required', 'exists:sections,id'],
            'academic_period_id' => ['required', 'exists:academic_periods,id'],
            'enrollment_date' => ['required', 'date'],
            'status' => ['required', 'in:active,inactive,transferred,graduated,withdrawn'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        // Check if changing to active and student already has active enrollment
        if ($validated['status'] === 'active' && $enrollment->status !== 'active') {
            $exists = Enrollment::where('student_id', $validated['student_id'])
                ->where('academic_period_id', $validated['academic_period_id'])
                ->where('status', 'active')
                ->where('id', '!=', $enrollment->id)
                ->exists();

            if ($exists) {
                return back()->withErrors(['status' => 'El estudiante ya tiene una inscripción activa en este período.']);
            }
        }

        $enrollment->update($validated);

        return back()->with('success', 'Inscripción actualizada correctamente.');
    }

    /**
     * Remove the specified enrollment.
     */
    public function destroy(Enrollment $enrollment)
    {
        $enrollment->delete();

        return back()->with('success', 'Inscripción eliminada correctamente.');
    }
}
