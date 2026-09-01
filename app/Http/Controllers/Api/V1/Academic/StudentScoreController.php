<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\StudentScore;
use App\Models\SubjectAssignment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentScoreController extends Controller
{
    /**
     * Listar calificaciones
     */
    public function index(Request $request): JsonResponse
    {
        $query = StudentScore::with([
            'student',
            'subjectAssignment.subject',
            'subjectAssignment.section.grade',
            'term',
            'gradedBy',
        ]);

        $user = Auth::user();

        // Los estudiantes solo ven sus propias calificaciones
        if ($user->hasRole('student')) {
            $query->where('student_id', $user->id);
        }

        // Los representantes solo ven las de sus estudiantes vinculados
        if ($user->hasRole('guardian')) {
            $studentIds = $user->students()->pluck('users.id');
            $query->whereIn('student_id', $studentIds);
        }

        // Los profesores solo ven las de sus asignaciones
        if ($user->hasRole('teacher')) {
            $query->whereHas('subjectAssignment', function ($q) use ($user) {
                $q->where('teacher_id', $user->id);
            });
        }

        // Filtrar por estudiante
        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        // Filtrar por asignación de materia
        if ($request->has('subject_assignment_id')) {
            $query->where('subject_assignment_id', $request->subject_assignment_id);
        }

        // Filtrar por lapso
        if ($request->has('term_id')) {
            $query->where('term_id', $request->term_id);
        }

        // Filtrar solo notas finales
        if ($request->has('is_final') && $request->is_final) {
            $query->where('is_final', true);
        }

        // MEJORA-05: Paginación para evitar cargar miles de registros
        $perPage = min($request->get('per_page', 50), 200); // máx 200 por página
        $scores = $query->orderBy('term_id')->paginate($perPage);

        return $this->sendResponse($scores, 'Calificaciones obtenidas exitosamente');
    }

    /**
     * Registrar una calificación
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'subject_assignment_id' => 'required|exists:subject_assignments,id',
            'term_id' => 'required|exists:terms,id',
            'score' => 'required|numeric|min:0|max:20',
            'observations' => 'nullable|string',
            'is_final' => 'boolean',
        ]);

        $student = User::findOrFail($validated['student_id']);
        $assignment = SubjectAssignment::findOrFail($validated['subject_assignment_id']);

        // Verificar que el estudiante tenga rol de estudiante
        if (! $student->hasRole('student')) {
            return $this->sendError('El usuario no es un estudiante', [], 422);
        }

        // Autorizar creación para la asignación específica
        $this->authorize('create', [StudentScore::class, $assignment]);

        // Verificar que no exista ya una calificación
        $exists = StudentScore::where('student_id', $validated['student_id'])
            ->where('subject_assignment_id', $validated['subject_assignment_id'])
            ->where('term_id', $validated['term_id'])
            ->exists();

        if ($exists) {
            return $this->sendError(
                'Ya existe una calificación para este estudiante en esta materia y lapso',
                [],
                409
            );
        }

        $score = StudentScore::create([
            'student_id' => $validated['student_id'],
            'subject_assignment_id' => $validated['subject_assignment_id'],
            'term_id' => $validated['term_id'],
            'score' => $validated['score'],
            'observations' => $validated['observations'] ?? null,
            'graded_by' => Auth::id(),
            'graded_at' => now(),
            'is_final' => $validated['is_final'] ?? false,
        ]);

        $score->load(['student', 'subjectAssignment.subject', 'term', 'gradedBy']);

        return $this->sendResponse($score, 'Calificación registrada exitosamente', 201);
    }

    /**
     * Mostrar una calificación específica
     */
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

    /**
     * Actualizar una calificación
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $score = StudentScore::find($id);

        if (is_null($score)) {
            return $this->sendError('Calificación no encontrada');
        }

        $this->authorize('update', $score);

        $validated = $request->validate([
            'score' => 'sometimes|numeric|min:0|max:20',
            'observations' => 'nullable|string',
            'is_final' => 'boolean',
        ]);

        $score->update($validated);
        $score->load(['student', 'subjectAssignment.subject', 'term', 'gradedBy']);

        return $this->sendResponse($score, 'Calificación actualizada exitosamente');
    }

    /**
     * Eliminar una calificación
     */
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

    /**
     * Obtener boleta de un estudiante (todas las notas por lapso)
     */
    public function reportCard(int $studentId, int $termId): JsonResponse
    {
        $this->authorize('reportCard', [StudentScore::class, $studentId]);

        $student = User::find($studentId);

        if (is_null($student) || ! $student->hasRole('student')) {
            return $this->sendError('Estudiante no encontrado');
        }

        $term = Term::with('academicPeriod')->find($termId);

        if (is_null($term)) {
            return $this->sendError('Lapso no encontrado');
        }

        $scores = StudentScore::with(['subjectAssignment.subject'])
            ->where('student_id', $studentId)
            ->where('term_id', $termId)
            ->get();

        // Calcular promedio
        $average = $scores->avg('score');

        $reportCard = [
            'student' => $student,
            'term' => $term,
            'scores' => $scores,
            'average' => round($average, 2),
            'total_subjects' => $scores->count(),
            'passed' => $scores->where('score', '>=', 10)->count(),
            'failed' => $scores->where('score', '<', 10)->count(),
        ];

        return $this->sendResponse($reportCard, 'Boleta obtenida exitosamente');
    }

    /**
     * Obtener calificaciones de un estudiante por período académico
     */
    public function byStudent(int $studentId): JsonResponse
    {
        $this->authorize('viewByStudent', [StudentScore::class, $studentId]);

        $scores = StudentScore::with([
            'subjectAssignment.subject',
            'term.academicPeriod',
        ])
            ->where('student_id', $studentId)
            ->orderBy('term_id')
            ->get()
            ->groupBy('term_id');

        return $this->sendResponse($scores, 'Calificaciones del estudiante obtenidas exitosamente');
    }

    /**
     * Registrar calificaciones masivas para una materia
     */
    public function bulkStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject_assignment_id' => 'required|exists:subject_assignments,id',
            'term_id' => 'required|exists:terms,id',
            'scores' => 'required|array|min:1',
            'scores.*.student_id' => 'required|exists:users,id',
            'scores.*.score' => 'required|numeric|min:0|max:20',
            'scores.*.observations' => 'nullable|string',
        ]);

        $assignment = SubjectAssignment::findOrFail($validated['subject_assignment_id']);

        // Autorizar creación para la asignación específica
        $this->authorize('create', [StudentScore::class, $assignment]);

        $created = [];
        $errors = [];

        foreach ($validated['scores'] as $scoreData) {
            $student = User::findOrFail($scoreData['student_id']);
            if (! $student->hasRole('student')) {
                $errors[] = "El usuario ID {$scoreData['student_id']} no es un estudiante";

                continue;
            }

            // Verificar si ya existe
            $exists = StudentScore::where('student_id', $scoreData['student_id'])
                ->where('subject_assignment_id', $validated['subject_assignment_id'])
                ->where('term_id', $validated['term_id'])
                ->exists();

            if ($exists) {
                $errors[] = "Ya existe calificación para el estudiante ID {$scoreData['student_id']}";

                continue;
            }

            $score = StudentScore::create([
                'student_id' => $scoreData['student_id'],
                'subject_assignment_id' => $validated['subject_assignment_id'],
                'term_id' => $validated['term_id'],
                'score' => $scoreData['score'],
                'observations' => $scoreData['observations'] ?? null,
                'graded_by' => Auth::id(),
                'graded_at' => now(),
                'is_final' => false,
            ]);

            $created[] = $score;
        }

        $response = [
            'created' => count($created),
            'errors' => $errors,
        ];

        return $this->sendResponse($response, 'Calificaciones procesadas');
    }
}
