<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualScore extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'subject_assignment_id',
        'term_id',
        'title',
        'description',
        'score',
        'max_score',
        'graded_by',
        'graded_at',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'graded_at' => 'datetime',
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
     * Obtener el porcentaje de la nota
     */
    public function getPercentageAttribute(): float
    {
        return $this->max_score > 0 ? ($this->score / $this->max_score) * 100 : 0;
    }

    /**
     * Obtener la nota en escala de 20
     */
    public function getScaleOf20Attribute(): float
    {
        return $this->max_score > 0 ? ($this->score / $this->max_score) * 20 : 0;
    }
}
