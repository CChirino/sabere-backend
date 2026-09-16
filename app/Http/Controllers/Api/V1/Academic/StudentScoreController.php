<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\StudentScore;
use App\Services\StudentScoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentScoreController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = app(StudentScoreService::class)->queryForUser($request);
        $perPage = app(StudentScoreService::class)->perPageForScores($request);
        $scores = $query->orderBy('term_id')->paginate($perPage);

        return $this->sendPaginatedResponse($scores, 'Calificaciones obtenidas exitosamente');
    }

    public function store(Request $request): JsonResponse
    {
        $assignment = \App\Models\SubjectAssignment::findOrFail($request->input('subject_assignment_id'));

        $this->authorize('create', [StudentScore::class, $assignment]);

        try {
            $score = app(StudentScoreService::class)->create($request);
        } catch (\InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), [], $e->getMessage() === 'El usuario no es un estudiante' ? 422 : 409);
        }

        return $this->sendResponse($score, 'Calificación registrada exitosamente', 201);
    }

    public function show(int $id): JsonResponse
    {
        $score = StudentScore::with([
            'student',
            'subjectAssignment.subject',
            'subjectAssignment.section.grade.educationLevel',
            'term',
            'gradedBy',
        ])->find($id);

        if (is_null($score)) {
            return $this->sendError('Calificación no encontrada');
        }

        $this->authorize('view', $score);

        return $this->sendResponse($score, 'Calificación obtenida exitosamente');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $score = StudentScore::find($id);

        if (is_null($score)) {
            return $this->sendError('Calificación no encontrada');
        }

        $this->authorize('update', $score);

        $score = app(StudentScoreService::class)->update($score, $request);

        return $this->sendResponse($score, 'Calificación actualizada exitosamente');
    }

    public function destroy(int $id): JsonResponse
    {
        $score = StudentScore::find($id);

        if (is_null($score)) {
            return $this->sendError('Calificación no encontrada');
        }

        $this->authorize('delete', $score);

        $score->delete();

        return $this->sendResponse(null, 'Calificación eliminada exitosamente');
    }

    public function reportCard(int $studentId, int $termId): JsonResponse
    {
        $this->authorize('reportCard', [StudentScore::class, $studentId]);

        try {
            $reportCard = app(StudentScoreService::class)->reportCard($studentId, $termId);
        } catch (\InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), [], 404);
        }

        return $this->sendResponse($reportCard, 'Boleta obtenida exitosamente');
    }

    public function byStudent(int $studentId): JsonResponse
    {
        $this->authorize('viewByStudent', [StudentScore::class, $studentId]);

        try {
            $scores = app(StudentScoreService::class)->byStudent($studentId);
        } catch (\InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), [], 404);
        }

        return $this->sendResponse($scores, 'Calificaciones del estudiante obtenidas exitosamente');
    }

    public function bulkStore(Request $request): JsonResponse
    {
        $assignment = \App\Models\SubjectAssignment::findOrFail($request->input('subject_assignment_id'));

        $this->authorize('create', [StudentScore::class, $assignment]);

        $result = app(StudentScoreService::class)->bulkCreate($request);

        return $this->sendResponse($result, 'Calificaciones procesadas');
    }
}
