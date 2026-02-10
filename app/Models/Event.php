<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academic_period_id',
        'created_by',
        'title',
        'description',
        'type',
        'start_date',
        'end_date',
        'all_day',
        'location',
        'color',
        'visibility',
        'send_notification',
        'status',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'all_day' => 'boolean',
        'send_notification' => 'boolean',
        'status' => 'boolean',
    ];

    /**
     * Colores por defecto según el tipo de evento.
     */
    public const TYPE_COLORS = [
        'academic' => '#3B82F6',
        'sports' => '#10B981',
        'cultural' => '#8B5CF6',
        'administrative' => '#F59E0B',
    ];

    /**
     * Etiquetas de tipo en español.
     */
    public const TYPE_LABELS = [
        'academic' => 'Académico',
        'sports' => 'Deportivo',
        'cultural' => 'Cultural',
        'administrative' => 'Administrativo',
    ];

    /**
     * Etiquetas de visibilidad en español.
     */
    public const VISIBILITY_LABELS = [
        'all' => 'Todos',
        'teachers' => 'Solo Profesores',
        'students' => 'Solo Estudiantes',
        'staff' => 'Solo Personal',
    ];

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Obtener la etiqueta del tipo.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    /**
     * Obtener el color del evento (usa el color personalizado o el del tipo).
     */
    public function getDisplayColorAttribute(): string
    {
        return $this->color ?? self::TYPE_COLORS[$this->type] ?? '#6B7280';
    }

    /**
     * Verificar si el evento es visible para un usuario según su rol.
     */
    public function isVisibleForRole(string $role): bool
    {
        if ($this->visibility === 'all') {
            return true;
        }

        if ($this->visibility === 'staff' && in_array($role, ['admin', 'director', 'coordinator'])) {
            return true;
        }

        if ($this->visibility === 'teachers' && in_array($role, ['admin', 'director', 'coordinator', 'teacher'])) {
            return true;
        }

        if ($this->visibility === 'students' && in_array($role, ['admin', 'director', 'coordinator', 'student', 'guardian'])) {
            return true;
        }

        return false;
    }

    /**
     * Scope para filtrar eventos visibles según el rol del usuario.
     */
    public function scopeVisibleForRole($query, string $role)
    {
        if (in_array($role, ['admin', 'director'])) {
            return $query;
        }

        return $query->where(function ($q) use ($role) {
            $q->where('visibility', 'all');

            if (in_array($role, ['coordinator'])) {
                $q->orWhere('visibility', 'staff')
                  ->orWhere('visibility', 'teachers');
            }

            if ($role === 'teacher') {
                $q->orWhere('visibility', 'teachers');
            }

            if (in_array($role, ['student', 'guardian'])) {
                $q->orWhere('visibility', 'students');
            }
        });
    }

    /**
     * Scope para filtrar eventos activos.
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope para filtrar eventos próximos.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>=', now())->orderBy('start_date');
    }
}
