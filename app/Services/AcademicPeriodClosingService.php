<?php

namespace App\Services;

use App\Models\AcademicPeriod;
use App\Models\AnnualAverage;
use App\Models\AnnualSubjectScore;
use App\Models\RecoveryRegistration;
use App\Models\StudentPromotion;
use App\Models\StudentScore;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AcademicPeriodClosingService
{
    /**
     * Cerrar un período académico: calcular notas anuales, promedios y promoción.
     */
    public static function close(AcademicPeriod $period, int $decidedBy, ?int $toGradeId = null): array
    {
        $results = [];

        DB::transaction(function () use ($period, $decidedBy, $toGradeId, &$results) {
            $students = User::whereHas('enrollments', function ($q) use ($period) {
                $q->where('academic_period_id', $period->id)
                    ->where('status', 'active');
            })->get();

            foreach ($students as $student) {
                $results[] = self::processStudent($student, $period, $decidedBy, $toGradeId);
            }
        });

        return $results;
    }

    private static function processStudent(User $student, AcademicPeriod $period, int $decidedBy, ?int $toGradeId): array
    {
        $scores = StudentScore::with(['subjectAssignment.subject', 'term'])
            ->where('student_id', $student->id)
            ->where('is_final', true)
            ->whereHas('term', function ($q) use ($period) {
                $q->where('academic_period_id', $period->id);
            })
            ->get();

        $subjectScores = [];
        $failedCount = 0;
        $recoveryCount = 0;

        foreach ($scores->groupBy(fn ($score) => $score->subjectAssignment->subject_id) as $subjectId => $termScores) {
            $weighted = $termScores->sum(fn (StudentScore $score) => $score->score * ($score->term->weight / 100));
            $final = round($weighted, 2);
            $letter = self::toLetterGrade($final);

            $status = 'promoted';
            $isPending = false;

            if ($final < 10) {
                $failedCount++;
                if ($failedCount <= 2) {
                    $status = 'failed';
                    $isPending = true;
                    $recoveryCount++;
                } else {
                    $status = 'failed';
                }
            }

            AnnualSubjectScore::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'subject_id' => $subjectId,
                    'academic_period_id' => $period->id,
                ],
                [
                    'final_score' => $final,
                    'letter_grade' => $letter,
                    'status' => $status,
                    'is_pending' => $isPending,
                ]
            );

            $subjectScores[] = $final;

            if ($status === 'failed' || $status === 'recovery') {
                RecoveryRegistration::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'subject_id' => $subjectId,
                        'academic_period_id' => $period->id,
                    ],
                    [
                        'status' => 'pending',
                        'recovery_score' => null,
                    ]
                );
            }
        }

        $average = count($subjectScores) > 0
            ? round(array_sum($subjectScores) / count($subjectScores), 2)
            : 0;
        $averageLetter = self::toLetterGrade($average);

        $promotionStatus = self::promotionStatus($average, $failedCount);

        AnnualAverage::updateOrCreate(
            [
                'student_id' => $student->id,
                'academic_period_id' => $period->id,
            ],
            [
                'average_score' => $average,
                'letter_grade' => $averageLetter,
                'status' => $promotionStatus,
            ]
        );

        $fromGrade = $student->activeEnrollment()?->section?->grade_id;

        StudentPromotion::updateOrCreate(
            [
                'student_id' => $student->id,
                'academic_period_id' => $period->id,
            ],
            [
                'from_grade_id' => $fromGrade,
                'to_grade_id' => $toGradeId,
                'status' => $promotionStatus,
                'decision' => "Cierre automático del período. Promedio: {$average}. Materias reprobadas: {$failedCount}.",
                'decided_by' => $decidedBy,
                'decided_at' => now(),
            ]
        );

        return [
            'student_id' => $student->id,
            'average' => $average,
            'status' => $promotionStatus,
            'failed_subjects' => $failedCount,
        ];
    }

    private static function toLetterGrade(float $score): string
    {
        return match (true) {
            $score >= 18 => 'A',
            $score >= 15 => 'B',
            $score >= 12 => 'C',
            $score >= 10 => 'D',
            default => 'E',
        };
    }

    private static function promotionStatus(float $average, int $failedCount): string
    {
        if ($average < 10 || $failedCount >= 3) {
            return 'repeating';
        }

        if ($failedCount >= 1) {
            return 'conditional';
        }

        return 'promoted';
    }
}
