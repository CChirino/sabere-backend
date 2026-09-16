<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentApplication extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academic_period_id',
        'grade_id',
        'first_name',
        'last_name',
        'birth_date',
        'gender',
        'id_number',
        'nationality',
        'address',
        'current_school',
        'status',
        'notes',
        'rejection_reason',
        'processed_by',
        'processed_at',
        'created_by',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'processed_at' => 'datetime',
    ];

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function guardians(): HasMany
    {
        return $this->hasMany(StudentApplicationGuardian::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(StudentApplicationDocument::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
