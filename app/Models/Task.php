<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'subject_assignment_id',
        'term_id',
        'title',
        'description',
        'instructions',
        'type',
        'max_score',
        'weight',
        'due_date',
        'available_from',
        'is_published',
        'status',
    ];

    protected $casts = [
        'max_score' => 'decimal:2',
        'weight' => 'decimal:2',
        'due_date' => 'datetime',
        'available_from' => 'datetime',
        'is_published' => 'boolean',
        'status' => 'boolean',
    ];

    public function subjectAssignment(): BelongsTo
    {
        return $this->belongsTo(SubjectAssignment::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(TaskSubmission::class);
    }

    /**
     * Obtener el profesor que creó la tarea
     */
    public function teacher()
    {
        return $this->subjectAssignment->teacher();
    }

    /**
     * Obtener la materia de la tarea
     */
    public function subject()
    {
        return $this->subjectAssignment->subject();
    }

    /**
     * Verificar si la tarea está disponible para los estudiantes
     */
    public function isAvailable(): bool
    {
        if (!$this->is_published) {
            return false;
        }

        $now = now();

        if ($this->available_from && $now < $this->available_from) {
            return false;
        }

        return true;
    }

    /**
     * Verificar si la tarea está vencida
     */
    public function isOverdue(): bool
    {
        return $this->due_date && now() > $this->due_date;
    }
}
