<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskSubmission extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'task_id',
        'student_id',
        'content',
        'file_path',
        'submitted_at',
        'score',
        'feedback',
        'graded_by',
        'graded_at',
        'status',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'score' => 'decimal:2',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    /**
     * Verificar si la entrega fue tardía
     */
    public function isLate(): bool
    {
        if (!$this->submitted_at || !$this->task->due_date) {
            return false;
        }

        return $this->submitted_at > $this->task->due_date;
    }

    /**
     * Verificar si la entrega ha sido calificada
     */
    public function isGraded(): bool
    {
        return $this->status === 'graded' && $this->score !== null;
    }

    /**
     * Obtener el porcentaje de la nota
     */
    public function getScorePercentageAttribute(): ?float
    {
        if ($this->score === null || $this->task->max_score == 0) {
            return null;
        }

        return ($this->score / $this->task->max_score) * 100;
    }
}
