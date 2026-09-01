<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'evaluation_plan_id',
        'name',
        'type',
        'evaluation_mode',
        'weight',
        'max_score',
        'order',
        'evaluation_date',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'max_score' => 'decimal:2',
        'order' => 'integer',
        'evaluation_date' => 'date',
    ];

    public function evaluationPlan(): BelongsTo
    {
        return $this->belongsTo(EvaluationPlan::class);
    }

    public function studentScores(): HasMany
    {
        return $this->hasMany(StudentEvaluationScore::class);
    }

    public function isQualitative(): bool
    {
        return $this->evaluation_mode === 'qualitative';
    }

    public function isQuantitative(): bool
    {
        return $this->evaluation_mode === 'quantitative';
    }
}
