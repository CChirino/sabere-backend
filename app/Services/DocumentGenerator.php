<?php

namespace App\Services;

use App\Models\DocumentVerification;
use App\Models\StudentScore;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class DocumentGenerator
{
    /**
     * Generar un boletín de calificaciones por lapso.
     */
    public static function reportCard(int $studentId, int $termId, int $generatedBy): array
    {
        $student = User::findOrFail($studentId);
        $scores = StudentScore::with(['subjectAssignment.subject', 'term'])
            ->where('student_id', $studentId)
            ->where('term_id', $termId)
            ->get();

        $items = $scores->map(fn (StudentScore $score) => [
            'subject' => $score->subjectAssignment->subject->name,
            'score' => $score->score,
            'letter' => $score->letter_grade,
            'observations' => $score->observations,
        ]);

        $average = $scores->isNotEmpty() ? round($scores->avg('score'), 2) : null;
        $periodName = $scores->first()?->term?->name ?? 'Período';

        $hash = (string) Str::uuid();
        $verificationUrl = route('documents.verify', ['hash' => $hash]);

        $data = [
            'student' => $student,
            'scores' => $items,
            'average' => $average,
            'periodName' => $periodName,
            'hash' => $hash,
            'verificationUrl' => $verificationUrl,
        ];

        $pdf = Pdf::loadView('documents.report_card', $data);

        DocumentVerification::create([
            'document_type' => 'report_card',
            'document_id' => "{$studentId}-{$termId}",
            'hash' => $hash,
            'generated_by' => $generatedBy,
            'expires_at' => now()->addMonths(6),
            'metadata' => [
                'student_id' => $studentId,
                'term_id' => $termId,
                'average' => $average,
            ],
        ]);

        return [
            'pdf' => $pdf,
            'filename' => "boletin_{$student->id}_{$termId}.pdf",
            'hash' => $hash,
        ];
    }

    /**
     * Generar constancia de estudio.
     */
    public static function studyCertificate(int $studentId, int $academicPeriodId, int $generatedBy): array
    {
        $student = User::findOrFail($studentId);
        $enrollment = $student->enrollments()
            ->where('academic_period_id', $academicPeriodId)
            ->with('section.grade')
            ->first();

        $hash = (string) Str::uuid();
        $verificationUrl = route('documents.verify', ['hash' => $hash]);

        $data = [
            'student' => $student,
            'grade' => $enrollment?->section?->grade?->name,
            'section' => $enrollment?->section?->name,
            'periodName' => $enrollment?->academicPeriod?->name ?? 'Período actual',
            'hash' => $hash,
            'verificationUrl' => $verificationUrl,
        ];

        $pdf = Pdf::loadView('documents.study_certificate', $data);

        DocumentVerification::create([
            'document_type' => 'study_certificate',
            'document_id' => (string) $studentId,
            'hash' => $hash,
            'generated_by' => $generatedBy,
            'expires_at' => now()->addMonths(12),
            'metadata' => [
                'student_id' => $studentId,
                'academic_period_id' => $academicPeriodId,
            ],
        ]);

        return [
            'pdf' => $pdf,
            'filename' => "constancia_estudio_{$student->id}.pdf",
            'hash' => $hash,
        ];
    }
}
