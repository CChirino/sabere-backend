<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicPeriod extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'school_year',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => 'boolean',
    ];

    /**
     * Obtener el año escolar en formato legible.
     */
    public function getFormattedSchoolYearAttribute()
    {
        return str_replace('-', ' - ', $this->school_year);
    }

    /**
     * Obtener el año de inicio del período escolar.
     */
    public function getStartYearAttribute()
    {
        return explode('-', $this->school_year)[0];
    }

    /**
     * Obtener el año de fin del período escolar.
     */
    public function getEndYearAttribute()
    {
        return explode('-', $this->school_year)[1] ?? null;
    }

    /**
     * Los lapsos de este período académico.
     */
    public function terms(): HasMany
    {
        return $this->hasMany(Term::class);
    }

    /**
     * Las secciones de este período académico.
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    /**
     * Las inscripciones de este período académico.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Las asignaciones de materias de este período.
     */
    public function subjectAssignments(): HasMany
    {
        return $this->hasMany(SubjectAssignment::class);
    }
}
