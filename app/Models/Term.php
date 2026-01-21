<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Term extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academic_period_id',
        'name',
        'number',
        'start_date',
        'end_date',
        'weight',
        'status',
    ];

    protected $casts = [
        'number' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'weight' => 'decimal:2',
        'status' => 'boolean',
    ];

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function studentScores(): HasMany
    {
        return $this->hasMany(StudentScore::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Verificar si el lapso está activo (dentro del rango de fechas)
     */
    public function isActive(): bool
    {
        $today = now()->toDateString();
        return $this->start_date <= $today && $this->end_date >= $today;
    }
}
