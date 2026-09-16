<?php

namespace App\Services;

use App\Models\StudentScore;
use App\Models\SubjectAssignment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentScoreService
{
    public function queryForUser(Request $request): Builder
    {
        $query = StudentScore::with([
            'student',
            'subjectAssignment.subject',
            'subjectAssignment.section.grade',
            'term',
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

        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->has('subject_assignment_id')) {
            $query->where('subject_assignment_id', $request->subject_assignment_id);
        }

        if ($request->has('term_id')) {
            $query->where('term_id', $request->term_id);
        }

        if ($request->has('is_final') && $request->is_final) {
            $query->where('is_final', true);
        }

        return $query;
    }

    public function perPageForScores(Request $request): int
    {
        return min($request->get('per_page', 50), 200);
    }

    public function create(Request $request): StudentScore
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

        if (! $student->hasRole('student')) {
            throw new \InvalidArgumentException('El usuario no es un estudiante');
        }

        $exists = StudentScore::where('student_id', $validated['student_id'])
            ->where('subject_assignment_id', $validated['subject_assignment_id'])
            ->where('term_id', $validated['term_id'])
            ->exists();

        if ($exists) {
            throw new \InvalidArgumentException('Ya existe una calificación para este estudiante en esta materia y lapso');
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

        return $score;
    }

    public function reportCard(int $studentId, int $termId): array
    {
        $student = User::find($studentId);

        if (is_null($student) || ! $student->hasRole('student')) {
            throw new \InvalidArgumentException('Estudiante no encontrado');
        }

        $term = Term::with('academicPeriod')->find($termId);

        if (is_null($term)) {
            throw new \InvalidArgumentException('Lapso no encontrado');
        }

        $scores = StudentScore::with(['subjectAssignment.subject'])
            ->where('student_id', $studentId)
            ->where('term_id', $termId)
            ->get();

        $average = $scores->avg('score');

        return [
            'student' => $student,
            'term' => $term,
            'scores' => $scores,
            'average' => round($average, 2),
            'total_subjects' => $scores->count(),
            'passed' => $scores->where('score', '>=', 10)->count(),
            'failed' => $scores->where('score', '<', 10)->count(),
        ];
    }

    public function byStudent(int $studentId): mixed
    {
        $student = User::find($studentId);

        if (is_null($student) || ! $student->hasRole('student')) {
            throw new \InvalidArgumentException('Estudiante no encontrado');
        }

        return StudentScore::with([
            'subjectAssignment.subject',
            'term.academicPeriod',
        ])
            ->where('student_id', $studentId)
            ->orderBy('term_id')
            ->get()
            ->groupBy('term_id');
    }

    public function bulkCreate(Request $request): array
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

        $created = [];
        $errors = [];

        foreach ($validated['scores'] as $scoreData) {
            $student = User::findOrFail($scoreData['student_id']);

            if (! $student->hasRole('student')) {
                $errors[] = "El usuario ID {$scoreData['student_id']} no es un estudiante";

                continue;
            }

            $exists = StudentScore::where('student_id', $scoreData['student_id'])
                ->where('subject_assignment_id', $validated['subject_assignment_id'])
                ->where('term_id', $validated['term_id'])
                ->exists();

            if ($exists) {
                $errors[] = "Ya existe calificación para el estudiante ID {$scoreData['student_id']}";

                continue;
            }

            $created[] = StudentScore::create([
                'student_id' => $scoreData['student_id'],
                'subject_assignment_id' => $validated['subject_assignment_id'],
                'term_id' => $validated['term_id'],
                'score' => $scoreData['score'],
                'observations' => $scoreData['observations'] ?? null,
                'graded_by' => Auth::id(),
                'graded_at' => now(),
                'is_final' => false,
            ]);
        }

        return [
            'created' => count($created),
            'errors' => $errors,
        ];
    }

    public function update(StudentScore $score, Request $request): StudentScore
    {
        $validated = $request->validate([
            'score' => 'sometimes|numeric|min:0|max:20',
            'observations' => 'nullable|string',
            'is_final' => 'boolean',
        ]);

        $score->update($validated);
        $score->load(['student', 'subjectAssignment.subject', 'term', 'gradedBy']);

        return $score;
    }
}
