<?php

namespace App\Services;

use App\Models\ManualScore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManualScoreService
{
    public function queryForUser(Request $request): Builder
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

        return $query->orderBy('created_at', 'desc');
    }

    public function create(array $validated): ManualScore
    {
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

        return $score;
    }

    public function update(ManualScore $score, array $validated): ManualScore
    {
        $score->update($validated);
        $score->load(['student', 'subjectAssignment.subject', 'term', 'gradedBy']);

        return $score;
    }

    public function destroy(ManualScore $score): void
    {
        $score->delete();
    }

    public function bulkCreate(array $validated): int
    {
        $created = 0;

        foreach ($validated['scores'] as $scoreData) {
            if ($scoreData['score'] !== null && $scoreData['score'] !== '') {
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

        return $created;
    }
}
