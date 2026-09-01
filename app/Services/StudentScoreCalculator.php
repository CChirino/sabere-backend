<?php

namespace App\Services;

use App\Models\StudentEvaluationScore;
use App\Models\StudentScore;

class StudentScoreCalculator
{
    /**
     * Recalcular la nota definitiva del lapso a partir de las notas por ítem.
     */
    public static function recalculate(int $studentId, int $subjectAssignmentId, int $termId): StudentScore
    {
        $scores = StudentEvaluationScore::with('evaluationItem')
            ->where('student_id', $studentId)
            ->where('subject_assignment_id', $subjectAssignmentId)
            ->whereHas('evaluationItem.evaluationPlan', function ($q) use ($termId) {
                $q->where('term_id', $termId);
            })
            ->get();

        if ($scores->isEmpty()) {
            return StudentScore::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'subject_assignment_id' => $subjectAssignmentId,
                    'term_id' => $termId,
                ],
                [
                    'score' => 0,
                    'observations' => null,
                    'graded_by' => auth()->id(),
                    'graded_at' => now(),
                    'is_final' => false,
                ]
            );
        }

        $total = $scores->sum(fn (StudentEvaluationScore $score) => $score->weightedScore);

        $studentScore = StudentScore::updateOrCreate(
            [
                'student_id' => $studentId,
                'subject_assignment_id' => $subjectAssignmentId,
                'term_id' => $termId,
            ],
            [
                'score' => round($total, 2),
                'observations' => null,
                'graded_by' => auth()->id(),
                'graded_at' => now(),
                'is_final' => false,
            ]
        );

        return $studentScore;
    }
}
