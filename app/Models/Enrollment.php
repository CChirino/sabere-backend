<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'section_id',
        'academic_period_id',
        'enrollment_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'enrollment_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    /**
     * Obtener el grado del estudiante a través de la sección
     */
    public function grade()
    {
        return $this->section->grade;
    }

    /**
     * Verificar si la inscripción está activa
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
