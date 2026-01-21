<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grade extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'education_level_id',
        'name',
        'numeric_equivalent',
        'status',
    ];

    protected $casts = [
        'numeric_equivalent' => 'integer',
        'status' => 'boolean',
    ];

    /**
     * Obtener el nivel educativo al que pertenece este grado.
     */
    public function educationLevel(): BelongsTo
    {
        return $this->belongsTo(EducationLevel::class);
    }

    /**
     * Las secciones de este grado.
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    /**
     * Las materias asociadas a este grado.
     */
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'grade_subject')
            ->withPivot(['school_year', 'hours_per_week', 'is_optional', 'status'])
            ->withTimestamps();
    }
}
