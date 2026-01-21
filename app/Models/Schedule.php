<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'subject_assignment_id',
        'day_of_week',
        'start_time',
        'end_time',
        'classroom',
        'notes',
        'status',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'status' => 'boolean',
    ];

    /**
     * Días de la semana en español
     */
    public const DAYS = [
        'monday' => 'Lunes',
        'tuesday' => 'Martes',
        'wednesday' => 'Miércoles',
        'thursday' => 'Jueves',
        'friday' => 'Viernes',
        'saturday' => 'Sábado',
    ];

    public function subjectAssignment(): BelongsTo
    {
        return $this->belongsTo(SubjectAssignment::class);
    }

    /**
     * Obtener la materia
     */
    public function subject()
    {
        return $this->subjectAssignment->subject;
    }

    /**
     * Obtener el profesor
     */
    public function teacher()
    {
        return $this->subjectAssignment->teacher;
    }

    /**
     * Obtener la sección
     */
    public function section()
    {
        return $this->subjectAssignment->section;
    }

    /**
     * Obtener el nombre del día en español
     */
    public function getDayNameAttribute(): string
    {
        return self::DAYS[$this->day_of_week] ?? $this->day_of_week;
    }

    /**
     * Obtener el horario formateado
     */
    public function getTimeRangeAttribute(): string
    {
        $start = \Carbon\Carbon::parse($this->start_time)->format('h:i A');
        $end = \Carbon\Carbon::parse($this->end_time)->format('h:i A');
        return "{$start} - {$end}";
    }

    /**
     * Obtener duración en minutos
     */
    public function getDurationMinutesAttribute(): int
    {
        $start = \Carbon\Carbon::parse($this->start_time);
        $end = \Carbon\Carbon::parse($this->end_time);
        return $start->diffInMinutes($end);
    }

    /**
     * Verificar si hay conflicto con otro horario en la sección
     */
    public static function hasConflict(
        int $subjectAssignmentId,
        string $dayOfWeek,
        string $startTime,
        string $endTime,
        ?int $excludeId = null
    ): bool {
        $assignment = SubjectAssignment::find($subjectAssignmentId);
        if (!$assignment) return false;

        // Normalizar formato de tiempo a H:i:s
        $startTime = self::normalizeTime($startTime);
        $endTime = self::normalizeTime($endTime);

        // Verificar conflicto en la misma sección
        $query = self::whereHas('subjectAssignment', function ($q) use ($assignment) {
            $q->where('section_id', $assignment->section_id)
              ->where('academic_period_id', $assignment->academic_period_id);
        })
            ->where('day_of_week', $dayOfWeek)
            ->where('status', true)
            ->where(function ($q) use ($startTime, $endTime) {
                // El nuevo horario se superpone con uno existente si:
                // - El inicio del nuevo está dentro de uno existente
                // - El fin del nuevo está dentro de uno existente
                // - El nuevo contiene completamente a uno existente
                $q->whereRaw('TIME(start_time) < TIME(?)', [$endTime])
                  ->whereRaw('TIME(end_time) > TIME(?)', [$startTime]);
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Normalizar formato de tiempo a H:i:s
     */
    private static function normalizeTime(string $time): string
    {
        // Si ya tiene segundos, retornar tal cual
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
            return $time;
        }
        // Si solo tiene H:i, agregar :00
        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time . ':00';
        }
        return $time;
    }

    /**
     * Verificar si el profesor tiene conflicto de horario
     */
    public static function teacherHasConflict(
        int $teacherId,
        string $dayOfWeek,
        string $startTime,
        string $endTime,
        int $academicPeriodId,
        ?int $excludeId = null
    ): bool {
        // Normalizar formato de tiempo
        $startTime = self::normalizeTime($startTime);
        $endTime = self::normalizeTime($endTime);

        $query = self::whereHas('subjectAssignment', function ($q) use ($teacherId, $academicPeriodId) {
            $q->where('teacher_id', $teacherId)
              ->where('academic_period_id', $academicPeriodId);
        })
            ->where('day_of_week', $dayOfWeek)
            ->where('status', true)
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereRaw('TIME(start_time) < TIME(?)', [$endTime])
                  ->whereRaw('TIME(end_time) > TIME(?)', [$startTime]);
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
