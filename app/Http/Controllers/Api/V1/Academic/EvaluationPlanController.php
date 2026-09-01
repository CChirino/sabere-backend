<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\EvaluationItem;
use App\Models\EvaluationPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EvaluationPlanController extends Controller
{
    /**
     * Listar planes de evaluación
     */
    public function index(Request $request): JsonResponse
    {
        $query = EvaluationPlan::with(['subject', 'grade', 'section', 'term', 'items']);

        $user = Auth::user();

        if ($user->hasRole('teacher')) {
            $subjectIds = $user->subjectAssignments()->pluck('subject_id');
            $query->whereIn('subject_id', $subjectIds);
        }

        if ($user->hasRole('student')) {
            $query->whereHas('section.enrollments', function ($q) use ($user) {
                $q->where('student_id', $user->id);
            });
        }

        if ($user->hasRole('guardian')) {
            $studentIds = $user->students()->pluck('users.id');
            $query->whereHas('section.enrollments', function ($q) use ($studentIds) {
                $q->whereIn('student_id', $studentIds);
            });
        }

        if ($request->has('academic_period_id')) {
            $query->where('academic_period_id', $request->academic_period_id);
        }

        if ($request->has('term_id')) {
            $query->where('term_id', $request->term_id);
        }

        if ($request->has('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->has('grade_id')) {
            $query->where('grade_id', $request->grade_id);
        }

        if ($request->has('section_id')) {
            $query->where('section_id', $request->section_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $plans = $query->get();

        return $this->sendResponse($plans, 'Planes de evaluación obtenidos exitosamente');
    }

    /**
     * Crear un plan de evaluación
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'academic_period_id' => 'required|exists:academic_periods,id',
            'term_id' => 'required|exists:terms,id',
            'subject_id' => 'required|exists:subjects,id',
            'grade_id' => 'required|exists:grades,id',
            'section_id' => 'nullable|exists:sections,id',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:100',
            'items.*.type' => 'required|in:exam,quiz,project,homework,participation,other',
            'items.*.evaluation_mode' => 'required|in:qualitative,quantitative',
            'items.*.weight' => 'required|numeric|min:0|max:100',
            'items.*.max_score' => 'nullable|numeric|min:0',
            'items.*.order' => 'nullable|integer|min:0',
            'items.*.evaluation_date' => 'nullable|date',
        ]);

        $this->authorize('create', EvaluationPlan::class);

        $totalWeight = collect($validated['items'])->sum('weight');
        if (abs($totalWeight - 100) > 0.01) {
            return $this->sendError(
                'La suma de los pesos de los ítems debe ser exactamente 100%',
                ['total_weight' => $totalWeight],
                422
            );
        }

        $plan = EvaluationPlan::create([
            'academic_period_id' => $validated['academic_period_id'],
            'term_id' => $validated['term_id'],
            'subject_id' => $validated['subject_id'],
            'grade_id' => $validated['grade_id'],
            'section_id' => $validated['section_id'] ?? null,
            'status' => 'draft',
        ]);

        $this->saveItems($plan, $validated['items']);

        $plan->load(['subject', 'grade', 'section', 'term', 'items']);

        return $this->sendResponse($plan, 'Plan de evaluación creado exitosamente', 201);
    }

    /**
     * Mostrar un plan específico
     */
    public function show(int $id): JsonResponse
    {
        $plan = EvaluationPlan::with(['subject', 'grade', 'section', 'term', 'items'])->find($id);

        if (is_null($plan)) {
            return $this->sendError('Plan de evaluación no encontrado');
        }

        $this->authorize('view', $plan);

        return $this->sendResponse($plan, 'Plan de evaluación obtenido exitosamente');
    }

    /**
     * Actualizar un plan en borrador
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $plan = EvaluationPlan::find($id);

        if (is_null($plan)) {
            return $this->sendError('Plan de evaluación no encontrado');
        }

        $this->authorize('update', $plan);

        if ($plan->status !== 'draft' && $plan->status !== 'rejected') {
            return $this->sendError(
                'Solo se puede editar un plan que esté en borrador o rechazado',
                [],
                422
            );
        }

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:100',
            'items.*.type' => 'required|in:exam,quiz,project,homework,participation,other',
            'items.*.evaluation_mode' => 'required|in:qualitative,quantitative',
            'items.*.weight' => 'required|numeric|min:0|max:100',
            'items.*.max_score' => 'nullable|numeric|min:0',
            'items.*.order' => 'nullable|integer|min:0',
            'items.*.evaluation_date' => 'nullable|date',
        ]);

        $totalWeight = collect($validated['items'])->sum('weight');
        if (abs($totalWeight - 100) > 0.01) {
            return $this->sendError(
                'La suma de los pesos de los ítems debe ser exactamente 100%',
                ['total_weight' => $totalWeight],
                422
            );
        }

        $plan->items()->delete();
        $this->saveItems($plan, $validated['items']);

        if ($plan->status === 'rejected') {
            $plan->status = 'draft';
            $plan->approved_at = null;
            $plan->approved_by = null;
            $plan->notes = null;
            $plan->save();
        }

        $plan->load(['subject', 'grade', 'section', 'term', 'items']);

        return $this->sendResponse($plan, 'Plan de evaluación actualizado exitosamente');
    }

    /**
     * Eliminar un plan
     */
    public function destroy(int $id): JsonResponse
    {
        $plan = EvaluationPlan::find($id);

        if (is_null($plan)) {
            return $this->sendError('Plan de evaluación no encontrado');
        }

        $this->authorize('delete', $plan);

        $plan->items()->delete();
        $plan->delete();

        return $this->sendResponse(null, 'Plan de evaluación eliminado exitosamente');
    }

    /**
     * Enviar plan a aprobación
     */
    public function submit(Request $request, int $id): JsonResponse
    {
        $plan = EvaluationPlan::find($id);

        if (is_null($plan)) {
            return $this->sendError('Plan de evaluación no encontrado');
        }

        $this->authorize('submit', $plan);

        if ($plan->status !== 'draft' && $plan->status !== 'rejected') {
            return $this->sendError('Solo se puede enviar un plan en borrador o rechazado', [], 422);
        }

        $plan->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $plan->load(['subject', 'grade', 'section', 'term', 'items']);

        return $this->sendResponse($plan, 'Plan de evaluación enviado a aprobación');
    }

    /**
     * Aprobar plan
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $plan = EvaluationPlan::find($id);

        if (is_null($plan)) {
            return $this->sendError('Plan de evaluación no encontrado');
        }

        $this->authorize('approve', EvaluationPlan::class);

        if ($plan->status !== 'submitted') {
            return $this->sendError('Solo se puede aprobar un plan enviado', [], 422);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $plan->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'notes' => $validated['notes'] ?? null,
        ]);

        $plan->load(['subject', 'grade', 'section', 'term', 'items']);

        return $this->sendResponse($plan, 'Plan de evaluación aprobado exitosamente');
    }

    /**
     * Rechazar plan
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $plan = EvaluationPlan::find($id);

        if (is_null($plan)) {
            return $this->sendError('Plan de evaluación no encontrado');
        }

        $this->authorize('reject', EvaluationPlan::class);

        if ($plan->status !== 'submitted') {
            return $this->sendError('Solo se puede rechazar un plan enviado', [], 422);
        }

        $validated = $request->validate([
            'notes' => 'required|string',
        ]);

        $plan->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'notes' => $validated['notes'],
        ]);

        $plan->load(['subject', 'grade', 'section', 'term', 'items']);

        return $this->sendResponse($plan, 'Plan de evaluación rechazado');
    }

    /**
     * Recalcular notas definitivas del plan
     */
    public function recalculate(int $id): JsonResponse
    {
        $plan = EvaluationPlan::with('items.studentScores')->find($id);

        if (is_null($plan)) {
            return $this->sendError('Plan de evaluación no encontrado');
        }

        $this->authorize('update', $plan);

        $affected = 0;
        foreach ($plan->items as $item) {
            foreach ($item->studentScores as $studentScore) {
                \App\Services\StudentScoreCalculator::recalculate(
                    $studentScore->student_id,
                    $studentScore->subject_assignment_id,
                    $plan->term_id
                );
                $affected++;
            }
        }

        return $this->sendResponse(null, "Notas recalculadas para {$affected} registros");
    }

    private function saveItems(EvaluationPlan $plan, array $items): void
    {
        foreach ($items as $index => $item) {
            EvaluationItem::create([
                'evaluation_plan_id' => $plan->id,
                'name' => $item['name'],
                'type' => $item['type'],
                'evaluation_mode' => $item['evaluation_mode'],
                'weight' => $item['weight'],
                'max_score' => $item['max_score'] ?? null,
                'order' => $item['order'] ?? $index,
                'evaluation_date' => $item['evaluation_date'] ?? null,
            ]);
        }
    }
}
