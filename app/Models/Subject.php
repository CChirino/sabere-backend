<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'subject_area_id',
        'name',
        'code',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Obtener el área de conocimiento a la que pertenece esta materia.
     */
    public function subjectArea(): BelongsTo
    {
        return $this->belongsTo(SubjectArea::class);
    }

    /**
     * Las asignaciones de esta materia a secciones.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(SubjectAssignment::class);
    }

    /**
     * Los grados a los que está asociada esta materia.
     */
    public function grades()
    {
        return $this->belongsToMany(Grade::class, 'grade_subject')
            ->withPivot(['school_year', 'hours_per_week', 'is_optional', 'status'])
            ->withTimestamps();
    }
}
