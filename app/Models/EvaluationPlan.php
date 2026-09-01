<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academic_period_id',
        'term_id',
        'subject_id',
        'grade_id',
        'section_id',
        'status',
        'submitted_at',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'status' => 'string',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(EvaluationItem::class)->orderBy('order');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function totalWeight(): float
    {
        return $this->items()->sum('weight');
    }
}
