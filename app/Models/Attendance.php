<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Attendance extends Model
{
    // MEJORA-04: Audit logging para cambios en asistencia
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'student_id',
        'section_id',
        'subject_assignment_id',
        'academic_period_id',
        'recorded_by',
        'date',
        'status',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * MEJORA-04: Configuración del audit log para asistencia.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['student_id', 'status', 'notes', 'date', 'recorded_by'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('asistencia')
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'Asistencia registrada',
                'updated' => 'Asistencia modificada',
                'deleted' => 'Asistencia eliminada',
                default => "Asistencia: {$eventName}",
            });
    }

    const STATUS_PRESENT = 'present';

    const STATUS_ABSENT = 'absent';

    const STATUS_LATE = 'late';

    const STATUS_EXCUSED = 'excused';

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function subjectAssignment(): BelongsTo
    {
        return $this->belongsTo(SubjectAssignment::class);
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Calcular el porcentaje de asistencia de un estudiante en una sección/período
     */
    public static function calculateAttendancePercentage(
        int $studentId,
        int $sectionId,
        int $academicPeriodId,
        ?int $subjectAssignmentId = null
    ): array {
        $query = self::where('student_id', $studentId)
            ->where('section_id', $sectionId)
            ->where('academic_period_id', $academicPeriodId);

        if ($subjectAssignmentId) {
            $query->where('subject_assignment_id', $subjectAssignmentId);
        }

        $total = $query->count();

        if ($total === 0) {
            return [
                'total_classes' => 0,
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'excused' => 0,
                'percentage' => 100,
                'meets_requirement' => true,
            ];
        }

        $present = (clone $query)->where('status', self::STATUS_PRESENT)->count();
        $late = (clone $query)->where('status', self::STATUS_LATE)->count();
        $excused = (clone $query)->where('status', self::STATUS_EXCUSED)->count();
        $absent = (clone $query)->where('status', self::STATUS_ABSENT)->count();

        // Para el cálculo: presente + tarde + justificado cuentan como asistencia
        $attended = $present + $late + $excused;
        $percentage = round(($attended / $total) * 100, 2);

        return [
            'total_classes' => $total,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'excused' => $excused,
            'attended' => $attended,
            'percentage' => $percentage,
            'meets_requirement' => $percentage >= 75,
        ];
    }

    /**
     * Verificar si el estudiante cumple con el 75% de asistencia
     */
    public static function meetsAttendanceRequirement(
        int $studentId,
        int $sectionId,
        int $academicPeriodId,
        ?int $subjectAssignmentId = null
    ): bool {
        $stats = self::calculateAttendancePercentage(
            $studentId,
            $sectionId,
            $academicPeriodId,
            $subjectAssignmentId
        );

        return $stats['meets_requirement'];
    }
}
