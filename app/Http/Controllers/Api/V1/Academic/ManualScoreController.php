<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\ManualScore;
use App\Models\SubjectAssignment;
use App\Services\ManualScoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManualScoreController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = app(ManualScoreService::class)->queryForUser($request);
        $scores = $query->paginate($this->perPage($request));

        return $this->sendPaginatedResponse($scores, 'Notas manuales obtenidas exitosamente');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'subject_assignment_id' => 'required|exists:subject_assignments,id',
            'term_id' => 'required|exists:terms,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'score' => 'required|numeric|min:0',
            'max_score' => 'required|numeric|min:0.01',
        ]);

        $assignment = SubjectAssignment::find($validated['subject_assignment_id']);

        if ($assignment->teacher_id !== Auth::id() && ! Auth::user()->hasAnyRole(['admin', 'director', 'coordinator'])) {
            return response()->json(['message' => 'No tienes permiso para agregar notas a esta materia'], 403);
        }

        if ($validated['score'] > $validated['max_score']) {
            return response()->json(['message' => 'La nota no puede ser mayor al máximo permitido'], 422);
        }

        $score = app(ManualScoreService::class)->create($validated);

        return response()->json(['data' => $score], 201);
    }

    public function show(int $id): JsonResponse
    {
        $score = ManualScore::with(['student', 'subjectAssignment.subject', 'term', 'gradedBy'])->find($id);

        if (! $score) {
            return response()->json(['message' => 'Nota manual no encontrada'], 404);
        }

        return response()->json(['data' => $score]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $score = ManualScore::find($id);

        if (! $score) {
            return response()->json(['message' => 'Nota manual no encontrada'], 404);
        }

        $assignment = $score->subjectAssignment;

        if ($assignment->teacher_id !== Auth::id() && ! Auth::user()->hasAnyRole(['admin', 'director', 'coordinator'])) {
            return response()->json(['message' => 'No tienes permiso para modificar esta nota'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:500',
            'score' => 'sometimes|numeric|min:0',
            'max_score' => 'sometimes|numeric|min:0.01',
        ]);

        $newScore = $validated['score'] ?? $score->score;
        $newMaxScore = $validated['max_score'] ?? $score->max_score;

        if ($newScore > $newMaxScore) {
            return response()->json(['message' => 'La nota no puede ser mayor al máximo permitido'], 422);
        }

        $score = app(ManualScoreService::class)->update($score, $validated);

        return response()->json(['data' => $score]);
    }

    public function destroy(int $id): JsonResponse
    {
        $score = ManualScore::find($id);

        if (! $score) {
            return response()->json(['message' => 'Nota manual no encontrada'], 404);
        }

        $assignment = $score->subjectAssignment;

        if ($assignment->teacher_id !== Auth::id() && ! Auth::user()->hasAnyRole(['admin', 'director', 'coordinator'])) {
            return response()->json(['message' => 'No tienes permiso para eliminar esta nota'], 403);
        }

        app(ManualScoreService::class)->destroy($score);

        return response()->json(['message' => 'Nota manual eliminada exitosamente']);
    }

    public function storeBulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject_assignment_id' => 'required|exists:subject_assignments,id',
            'term_id' => 'required|exists:terms,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'max_score' => 'required|numeric|min:0.01',
            'scores' => 'required|array',
            'scores.*.student_id' => 'required|exists:users,id',
            'scores.*.score' => 'nullable|numeric|min:0',
        ]);

        $assignment = SubjectAssignment::find($validated['subject_assignment_id']);

        if ($assignment->teacher_id !== Auth::id() && ! Auth::user()->hasAnyRole(['admin', 'director', 'coordinator'])) {
            return response()->json(['message' => 'No tienes permiso para agregar notas a esta materia'], 403);
        }

        $created = app(ManualScoreService::class)->bulkCreate($validated);

        return response()->json(['data' => ['created' => $created]], 201);
    }
}
