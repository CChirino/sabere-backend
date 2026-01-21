<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentScore extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'subject_assignment_id',
        'term_id',
        'score',
        'observations',
        'graded_by',
        'graded_at',
        'is_final',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'graded_at' => 'datetime',
        'is_final' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function subjectAssignment(): BelongsTo
    {
        return $this->belongsTo(SubjectAssignment::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    /**
     * Obtener la materia
     */
    public function subject()
    {
        return $this->subjectAssignment->subject();
    }

    /**
     * Verificar si el estudiante aprobó (nota >= 10 en escala de 20)
     */
    public function isPassing(): bool
    {
        return $this->score >= 10;
    }

    /**
     * Obtener la calificación en escala de 100
     */
    public function getScorePercentageAttribute(): float
    {
        return ($this->score / 20) * 100;
    }

    /**
     * Obtener la calificación literal (A, B, C, D, E)
     */
    public function getLetterGradeAttribute(): string
    {
        return match (true) {
            $this->score >= 18 => 'A',
            $this->score >= 15 => 'B',
            $this->score >= 12 => 'C',
            $this->score >= 10 => 'D',
            default => 'E',
        };
    }
}
