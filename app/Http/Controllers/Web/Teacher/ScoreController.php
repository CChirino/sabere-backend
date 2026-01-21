<?php

namespace App\Http\Controllers\Web\Teacher;

use App\Http\Controllers\Controller;
use App\Models\StudentScore;
use App\Models\SubjectAssignment;
use App\Models\Term;
use Illuminate\Http\Request;

class ScoreController extends Controller
{
    /**
     * Store or update a student score.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:users,id'],
            'subject_assignment_id' => ['required', 'exists:subject_assignments,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'score' => ['required', 'numeric', 'min:0', 'max:20'],
            'observations' => ['nullable', 'string', 'max:500'],
        ]);

        // Verify the teacher owns this assignment
        $assignment = SubjectAssignment::find($validated['subject_assignment_id']);
        if ($assignment->teacher_id !== auth()->id()) {
            return back()->withErrors(['error' => 'No tienes permiso para calificar esta materia.']);
        }

        // Create or update the score
        $score = StudentScore::updateOrCreate(
            [
                'student_id' => $validated['student_id'],
                'subject_assignment_id' => $validated['subject_assignment_id'],
                'term_id' => $validated['term_id'],
            ],
            [
                'score' => $validated['score'],
                'observations' => $validated['observations'] ?? null,
                'graded_by' => auth()->id(),
                'graded_at' => now(),
            ]
        );

        return back()->with('success', 'Calificación guardada correctamente.');
    }

    /**
     * Store multiple scores at once.
     */
    public function storeBulk(Request $request)
    {
        $validated = $request->validate([
            'subject_assignment_id' => ['required', 'exists:subject_assignments,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'scores' => ['required', 'array'],
            'scores.*.student_id' => ['required', 'exists:users,id'],
            'scores.*.score' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'scores.*.observations' => ['nullable', 'string', 'max:500'],
        ]);

        // Verify the teacher owns this assignment
        $assignment = SubjectAssignment::find($validated['subject_assignment_id']);
        if ($assignment->teacher_id !== auth()->id()) {
            return back()->withErrors(['error' => 'No tienes permiso para calificar esta materia.']);
        }

        foreach ($validated['scores'] as $scoreData) {
            if ($scoreData['score'] !== null && $scoreData['score'] !== '') {
                StudentScore::updateOrCreate(
                    [
                        'student_id' => $scoreData['student_id'],
                        'subject_assignment_id' => $validated['subject_assignment_id'],
                        'term_id' => $validated['term_id'],
                    ],
                    [
                        'score' => $scoreData['score'],
                        'observations' => $scoreData['observations'] ?? null,
                        'graded_by' => auth()->id(),
                        'graded_at' => now(),
                    ]
                );
            }
        }

        return back()->with('success', 'Calificaciones guardadas correctamente.');
    }

    /**
     * Mark scores as final.
     */
    public function finalize(Request $request)
    {
        $validated = $request->validate([
            'subject_assignment_id' => ['required', 'exists:subject_assignments,id'],
            'term_id' => ['required', 'exists:terms,id'],
        ]);

        // Verify the teacher owns this assignment
        $assignment = SubjectAssignment::find($validated['subject_assignment_id']);
        if ($assignment->teacher_id !== auth()->id()) {
            return back()->withErrors(['error' => 'No tienes permiso para finalizar estas calificaciones.']);
        }

        StudentScore::where('subject_assignment_id', $validated['subject_assignment_id'])
            ->where('term_id', $validated['term_id'])
            ->update(['is_final' => true]);

        return back()->with('success', 'Calificaciones finalizadas correctamente.');
    }
}
