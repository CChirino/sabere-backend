<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\EvaluationItem;
use App\Models\StudentEvaluationScore;
use App\Models\SubjectAssignment;
use App\Models\User;
use App\Services\StudentScoreCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentEvaluationScoreController extends Controller
{
    /**
     * Listar notas por ítem
     */
    public function index(Request $request): JsonResponse
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

        $scores = $query->get();

        return $this->sendResponse($scores, 'Notas por ítem obtenidas exitosamente');
    }

    /**
     * Crear/actualizar nota por ítem
     */
    public function store(Request $request): JsonResponse
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
            return $this->sendError('No se pueden cargar notas en un plan no aprobado', [], 403);
        }

        if ($item->isQuantitative() && ! isset($validated['score'])) {
            return $this->sendError('La nota numérica es requerida para ítems cuantitativos', [], 422);
        }

        if ($item->isQualitative() && ! isset($validated['letter_grade'])) {
            return $this->sendError('La letra es requerida para ítems cualitativos', [], 422);
        }

        $student = User::find($validated['student_id']);
        if (! $student->hasRole('student')) {
            return $this->sendError('El usuario no es un estudiante', [], 422);
        }

        $assignment = SubjectAssignment::find($validated['subject_assignment_id']);

        $this->authorize('create', [StudentEvaluationScore::class, $assignment]);

        if ($item->isQuantitative() && isset($validated['score']) && $item->max_score && $validated['score'] > $item->max_score) {
            return $this->sendError("La nota no puede exceder el máximo de {$item->max_score}", [], 422);
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

        StudentScoreCalculator::recalculate(
            $validated['student_id'],
            $validated['subject_assignment_id'],
            $item->evaluationPlan->term_id
        );

        $score->load(['student', 'subjectAssignment.subject', 'evaluationItem', 'gradedBy']);

        return $this->sendResponse($score, 'Nota por ítem registrada exitosamente', 201);
    }

    /**
     * Mostrar una nota por ítem
     */
    public function show(int $id): JsonResponse
    {
        $score = StudentEvaluationScore::with([
            'student',
            'subjectAssignment.subject',
            'evaluationItem.evaluationPlan',
            'gradedBy',
        ])->find($id);

        if (is_null($score)) {
            return $this->sendError('Nota por ítem no encontrada');
        }

        $this->authorize('view', $score);

        return $this->sendResponse($score, 'Nota por ítem obtenida exitosamente');
    }

    /**
     * Actualizar nota por ítem
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $score = StudentEvaluationScore::find($id);

        if (is_null($score)) {
            return $this->sendError('Nota por ítem no encontrada');
        }

        $this->authorize('update', $score);

        $item = $score->evaluationItem;

        if (! $item->evaluationPlan->isApproved()) {
            return $this->sendError('No se pueden modificar notas de un plan no aprobado', [], 403);
        }

        $validated = $request->validate([
            'score' => 'nullable|numeric|min:0',
            'letter_grade' => 'nullable|in:A,B,C,D,E',
            'observations' => 'nullable|string',
        ]);

        if ($item->isQuantitative() && isset($validated['score'])) {
            if ($item->max_score && $validated['score'] > $item->max_score) {
                return $this->sendError("La nota no puede exceder el máximo de {$item->max_score}", [], 422);
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

        StudentScoreCalculator::recalculate(
            $score->student_id,
            $score->subject_assignment_id,
            $item->evaluationPlan->term_id
        );

        $score->load(['student', 'subjectAssignment.subject', 'evaluationItem', 'gradedBy']);

        return $this->sendResponse($score, 'Nota por ítem actualizada exitosamente');
    }

    /**
     * Eliminar nota por ítem
     */
    public function destroy(int $id): JsonResponse
    {
        $score = StudentEvaluationScore::find($id);

        if (is_null($score)) {
            return $this->sendError('Nota por ítem no encontrada');
        }

        $this->authorize('delete', $score);

        $termId = $score->evaluationItem->evaluationPlan->term_id;
        $studentId = $score->student_id;
        $assignmentId = $score->subject_assignment_id;

        $score->delete();

        StudentScoreCalculator::recalculate($studentId, $assignmentId, $termId);

        return $this->sendResponse(null, 'Nota por ítem eliminada exitosamente');
    }
}
