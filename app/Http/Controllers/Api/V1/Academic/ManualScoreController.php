<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\ManualScore;
use App\Models\SubjectAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManualScoreController extends Controller
{
    /**
     * Listar notas manuales con filtros
     */
    public function index(Request $request): JsonResponse
    {
        $query = ManualScore::with(['student', 'subjectAssignment.subject', 'term', 'gradedBy']);

        if ($request->has('subject_assignment_id')) {
            $query->where('subject_assignment_id', $request->subject_assignment_id);
        }

        if ($request->has('term_id')) {
            $query->where('term_id', $request->term_id);
        }

        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        $scores = $query->orderBy('created_at', 'desc')->get();

        return response()->json(['data' => $scores]);
    }

    /**
     * Crear una nota manual
     */
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

        // Verificar que el profesor tenga permiso sobre esta asignación
        $assignment = SubjectAssignment::find($validated['subject_assignment_id']);
        if ($assignment->teacher_id !== Auth::id() && !Auth::user()->hasAnyRole(['admin', 'director', 'coordinator'])) {
            return response()->json(['message' => 'No tienes permiso para agregar notas a esta materia'], 403);
        }

        // Validar que la nota no exceda el máximo
        if ($validated['score'] > $validated['max_score']) {
            return response()->json(['message' => 'La nota no puede ser mayor al máximo permitido'], 422);
        }

        $score = ManualScore::create([
            'student_id' => $validated['student_id'],
            'subject_assignment_id' => $validated['subject_assignment_id'],
            'term_id' => $validated['term_id'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'score' => $validated['score'],
            'max_score' => $validated['max_score'],
            'graded_by' => Auth::id(),
            'graded_at' => now(),
        ]);

        $score->load(['student', 'subjectAssignment.subject', 'term', 'gradedBy']);

        return response()->json(['data' => $score], 201);
    }

    /**
     * Mostrar una nota manual específica
     */
    public function show(int $id): JsonResponse
    {
        $score = ManualScore::with(['student', 'subjectAssignment.subject', 'term', 'gradedBy'])->find($id);

        if (!$score) {
            return response()->json(['message' => 'Nota manual no encontrada'], 404);
        }

        return response()->json(['data' => $score]);
    }

    /**
     * Actualizar una nota manual
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $score = ManualScore::find($id);

        if (!$score) {
            return response()->json(['message' => 'Nota manual no encontrada'], 404);
        }

        // Verificar permisos
        $assignment = $score->subjectAssignment;
        if ($assignment->teacher_id !== Auth::id() && !Auth::user()->hasAnyRole(['admin', 'director', 'coordinator'])) {
            return response()->json(['message' => 'No tienes permiso para modificar esta nota'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:500',
            'score' => 'sometimes|numeric|min:0',
            'max_score' => 'sometimes|numeric|min:0.01',
        ]);

        // Validar que la nota no exceda el máximo
        $newScore = $validated['score'] ?? $score->score;
        $newMaxScore = $validated['max_score'] ?? $score->max_score;
        if ($newScore > $newMaxScore) {
            return response()->json(['message' => 'La nota no puede ser mayor al máximo permitido'], 422);
        }

        $score->update($validated);
        $score->load(['student', 'subjectAssignment.subject', 'term', 'gradedBy']);

        return response()->json(['data' => $score]);
    }

    /**
     * Eliminar una nota manual
     */
    public function destroy(int $id): JsonResponse
    {
        $score = ManualScore::find($id);

        if (!$score) {
            return response()->json(['message' => 'Nota manual no encontrada'], 404);
        }

        // Verificar permisos
        $assignment = $score->subjectAssignment;
        if ($assignment->teacher_id !== Auth::id() && !Auth::user()->hasAnyRole(['admin', 'director', 'coordinator'])) {
            return response()->json(['message' => 'No tienes permiso para eliminar esta nota'], 403);
        }

        $score->delete();

        return response()->json(['message' => 'Nota manual eliminada exitosamente']);
    }

    /**
     * Crear múltiples notas manuales a la vez (para todos los estudiantes)
     */
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

        // Verificar permisos
        $assignment = SubjectAssignment::find($validated['subject_assignment_id']);
        if ($assignment->teacher_id !== Auth::id() && !Auth::user()->hasAnyRole(['admin', 'director', 'coordinator'])) {
            return response()->json(['message' => 'No tienes permiso para agregar notas a esta materia'], 403);
        }

        $created = 0;
        foreach ($validated['scores'] as $scoreData) {
            if ($scoreData['score'] !== null && $scoreData['score'] !== '') {
                // Validar que no exceda el máximo
                if ($scoreData['score'] > $validated['max_score']) {
                    continue;
                }

                ManualScore::create([
                    'student_id' => $scoreData['student_id'],
                    'subject_assignment_id' => $validated['subject_assignment_id'],
                    'term_id' => $validated['term_id'],
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'score' => $scoreData['score'],
                    'max_score' => $validated['max_score'],
                    'graded_by' => Auth::id(),
                    'graded_at' => now(),
                ]);
                $created++;
            }
        }

        return response()->json(['data' => ['created' => $created]], 201);
    }
}
