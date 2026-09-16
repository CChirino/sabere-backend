<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\EvaluationPlan;
use App\Services\EvaluationPlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EvaluationPlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = app(EvaluationPlanService::class)->queryForUser($request);
        $plans = $query->paginate($this->perPage($request));

        return $this->sendPaginatedResponse($plans, 'Planes de evaluación obtenidos exitosamente');
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', EvaluationPlan::class);

        try {
            $plan = app(EvaluationPlanService::class)->create($request);
        } catch (\InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }

        return $this->sendResponse($plan, 'Plan de evaluación creado exitosamente', 201);
    }

    public function show(int $id): JsonResponse
    {
        $plan = EvaluationPlan::with(['subject', 'grade', 'section', 'term', 'items'])->find($id);

        if (is_null($plan)) {
            return $this->sendError('Plan de evaluación no encontrado');
        }

        $this->authorize('view', $plan);

        return $this->sendResponse($plan, 'Plan de evaluación obtenido exitosamente');
    }

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

        try {
            $plan = app(EvaluationPlanService::class)->update($plan, $request);
        } catch (\InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }

        return $this->sendResponse($plan, 'Plan de evaluación actualizado exitosamente');
    }

    public function destroy(int $id): JsonResponse
    {
        $plan = EvaluationPlan::find($id);

        if (is_null($plan)) {
            return $this->sendError('Plan de evaluación no encontrado');
        }

        $this->authorize('delete', $plan);

        app(EvaluationPlanService::class)->destroy($plan);

        return $this->sendResponse(null, 'Plan de evaluación eliminado exitosamente');
    }

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

        $plan = app(EvaluationPlanService::class)->submit($plan);

        return $this->sendResponse($plan, 'Plan de evaluación enviado a aprobación');
    }

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

        $plan = app(EvaluationPlanService::class)->approve($plan, $validated['notes'] ?? null);

        return $this->sendResponse($plan, 'Plan de evaluación aprobado exitosamente');
    }

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

        $plan = app(EvaluationPlanService::class)->reject($plan, $validated['notes']);

        return $this->sendResponse($plan, 'Plan de evaluación rechazado');
    }

    public function recalculate(int $id): JsonResponse
    {
        $plan = EvaluationPlan::find($id);

        if (is_null($plan)) {
            return $this->sendError('Plan de evaluación no encontrado');
        }

        $this->authorize('update', $plan);

        $affected = app(EvaluationPlanService::class)->recalculate($plan);

        return $this->sendResponse(null, "Notas recalculadas para {$affected} registros");
    }
}
