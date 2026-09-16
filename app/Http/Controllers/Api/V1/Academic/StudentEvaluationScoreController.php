<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\StudentEvaluationScore;
use App\Services\StudentEvaluationScoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentEvaluationScoreController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = app(StudentEvaluationScoreService::class)->queryForUser($request);
        $scores = $query->paginate($this->perPage($request));

        return $this->sendPaginatedResponse($scores, 'Notas por ítem obtenidas exitosamente');
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $score = app(StudentEvaluationScoreService::class)->store($request);
        } catch (\InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), [], 422);
        } catch (\RuntimeException $e) {
            return $this->sendError($e->getMessage(), [], 403);
        }

        return $this->sendResponse($score, 'Nota por ítem registrada exitosamente', 201);
    }

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

    public function update(Request $request, int $id): JsonResponse
    {
        $score = StudentEvaluationScore::find($id);

        if (is_null($score)) {
            return $this->sendError('Nota por ítem no encontrada');
        }

        $this->authorize('update', $score);

        try {
            $score = app(StudentEvaluationScoreService::class)->update($score, $request);
        } catch (\InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), [], 422);
        } catch (\RuntimeException $e) {
            return $this->sendError($e->getMessage(), [], 403);
        }

        return $this->sendResponse($score, 'Nota por ítem actualizada exitosamente');
    }

    public function destroy(int $id): JsonResponse
    {
        $score = StudentEvaluationScore::find($id);

        if (is_null($score)) {
            return $this->sendError('Nota por ítem no encontrada');
        }

        $this->authorize('delete', $score);

        app(StudentEvaluationScoreService::class)->destroy($score);

        return $this->sendResponse(null, 'Nota por ítem eliminada exitosamente');
    }
}
