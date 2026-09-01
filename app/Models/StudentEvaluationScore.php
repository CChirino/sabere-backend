<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StudentEvaluationScore extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'student_id',
        'subject_assignment_id',
        'evaluation_item_id',
        'score',
        'letter_grade',
        'graded_by',
        'graded_at',
        'observations',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'graded_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('notas_por_item')
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'Nota por ítem registrada',
                'updated' => 'Nota por ítem modificada',
                'deleted' => 'Nota por ítem eliminada',
                default => "Nota por ítem: {$eventName}",
            });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function subjectAssignment(): BelongsTo
    {
        return $this->belongsTo(SubjectAssignment::class);
    }

    public function evaluationItem(): BelongsTo
    {
        return $this->belongsTo(EvaluationItem::class);
    }

    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function getNumericValueAttribute(): float
    {
        if ($this->score !== null) {
            return (float) $this->score;
        }

        return match (strtoupper($this->letter_grade)) {
            'A' => 19,
            'B' => 16,
            'C' => 13,
            'D' => 10,
            'E' => 5,
            default => 0,
        };
    }

    public function getWeightedScoreAttribute(): float
    {
        return $this->numericValue * ((float) $this->evaluationItem->weight / 100);
    }
}
