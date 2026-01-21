<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'grade_id',
        'academic_period_id',
        'name',
        'capacity',
        'status',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'status' => 'boolean',
    ];

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function subjectAssignments(): HasMany
    {
        return $this->hasMany(SubjectAssignment::class);
    }

    /**
     * Mensajes del chat de la sección.
     */
    public function chatMessages(): HasMany
    {
        return $this->hasMany(SectionChatMessage::class);
    }

    /**
     * Obtener estudiantes inscritos en esta sección
     */
    public function students()
    {
        return $this->hasManyThrough(
            User::class,
            Enrollment::class,
            'section_id',
            'id',
            'id',
            'student_id'
        );
    }

    /**
     * Nombre completo de la sección (ej: "3° Grado A")
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->grade->name} {$this->name}";
    }
}
