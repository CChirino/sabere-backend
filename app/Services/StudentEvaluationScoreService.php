<?php

namespace App\Services;

use App\Models\EvaluationItem;
use App\Models\StudentEvaluationScore;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentEvaluationScoreService
{
    public function queryForUser(Request $request): Builder
    {
        $query = StudentEvaluationScore::with([
            'student',
            'subjectAssignment.subject',
            'evaluationItem.evaluationPlan',
            'gradedBy',
        ]);

        $user = Auth::user();

        if ($user->hasRole('student')) {
            $query->where('student_id', $user->id);
        }

        if ($user->hasRole('guardian')) {
            $studentIds = $user->students()->pluck('users.id');
            $query->whereIn('student_id', $studentIds);
        }

        if ($user->hasRole('teacher')) {
            $query->whereHas('subjectAssignment', function ($q) use ($user) {
                $q->where('teacher_id', $user->id);
            });
        }

        if ($request->has('evaluation_item_id')) {
            $query->where('evaluation_item_id', $request->evaluation_item_id);
        }

        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->has('subject_assignment_id')) {
            $query->where('subject_assignment_id', $request->subject_assignment_id);
        }

        return $query;
    }

    public function store(Request $request): StudentEvaluationScore
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'subject_assignment_id' => 'required|exists:subject_assignments,id',
            'evaluation_item_id' => 'required|exists:evaluation_items,id',
            'score' => 'nullable|numeric|min:0',
            'letter_grade' => 'nullable|in:A,B,C,D,E',
            'observations' => 'nullable|string',
        ]);

        $item = EvaluationItem::with('evaluationPlan')->findOrFail($validated['evaluation_item_id']);

        if (! $item->evaluationPlan->isApproved()) {
            throw new \RuntimeException('No se pueden cargar notas en un plan no aprobado');
        }

        if ($item->isQuantitative() && ! isset($validated['score'])) {
            throw new \InvalidArgumentException('La nota numérica es requerida para ítems cuantitativos');
        }

        if ($item->isQualitative() && ! isset($validated['letter_grade'])) {
            throw new \InvalidArgumentException('La letra es requerida para ítems cualitativos');
        }

        $student = User::find($validated['student_id']);

        if (! $student->hasRole('student')) {
            throw new \InvalidArgumentException('El usuario no es un estudiante');
        }

        $assignment = SubjectAssignment::find($validated['subject_assignment_id']);

        if ($item->isQuantitative() && isset($validated['score']) && $item->max_score && $validated['score'] > $item->max_score) {
            throw new \InvalidArgumentException("La nota no puede exceder el máximo de {$item->max_score}");
        }

        $score = StudentEvaluationScore::updateOrCreate(
            [
                'student_id' => $validated['student_id'],
                'subject_assignment_id' => $validated['subject_assignment_id'],
                'evaluation_item_id' => $validated['evaluation_item_id'],
            ],
            [
                'score' => $validated['score'] ?? null,
                'letter_grade' => $validated['letter_grade'] ?? null,
                'observations' => $validated['observations'] ?? null,
                'graded_by' => Auth::id(),
                'graded_at' => now(),
            ]
        );

        \App\Services\StudentScoreCalculator::recalculate(
            $validated['student_id'],
            $validated['subject_assignment_id'],
            $item->evaluationPlan->term_id
        );

        $score->load(['student', 'subjectAssignment.subject', 'evaluationItem', 'gradedBy']);

        return $score;
    }

    public function update(StudentEvaluationScore $score, Request $request): StudentEvaluationScore
    {
        $item = $score->evaluationItem;

        if (! $item->evaluationPlan->isApproved()) {
            throw new \RuntimeException('No se pueden modificar notas de un plan no aprobado');
        }

        $validated = $request->validate([
            'score' => 'nullable|numeric|min:0',
            'letter_grade' => 'nullable|in:A,B,C,D,E',
            'observations' => 'nullable|string',
        ]);

        if ($item->isQuantitative() && isset($validated['score'])) {
            if ($item->max_score && $validated['score'] > $item->max_score) {
                throw new \InvalidArgumentException("La nota no puede exceder el máximo de {$item->max_score}");
            }
            $score->score = $validated['score'];
            $score->letter_grade = null;
        }

        if ($item->isQualitative() && isset($validated['letter_grade'])) {
            $score->letter_grade = $validated['letter_grade'];
            $score->score = null;
        }

        $score->observations = $validated['observations'] ?? $score->observations;
        $score->graded_by = Auth::id();
        $score->graded_at = now();
        $score->save();

        \App\Services\StudentScoreCalculator::recalculate(
            $score->student_id,
            $score->subject_assignment_id,
            $item->evaluationPlan->term_id
        );

        $score->load(['student', 'subjectAssignment.subject', 'evaluationItem', 'gradedBy']);

        return $score;
    }

    public function destroy(StudentEvaluationScore $score): void
    {
        $termId = $score->evaluationItem->evaluationPlan->term_id;
        $studentId = $score->student_id;
        $assignmentId = $score->subject_assignment_id;

        $score->delete();

        \App\Services\StudentScoreCalculator::recalculate($studentId, $assignmentId, $termId);
    }
}
