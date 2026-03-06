<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reenrollment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'current_enrollment_id',
        'target_academic_period_id',
        'target_grade_id',
        'target_section_id',
        'status',
        'student_notes',
        'admin_notes',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    // ==================== RELACIONES ====================

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function currentEnrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'current_enrollment_id');
    }

    public function targetAcademicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class, 'target_academic_period_id');
    }

    public function targetGrade(): BelongsTo
    {
        return $this->belongsTo(Grade::class, 'target_grade_id');
    }

    public function targetSection(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'target_section_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // ==================== HELPERS ====================

    /**
     * Verificar si la solicitud está pendiente.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Verificar si la solicitud fue aprobada.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Verificar si la solicitud fue rechazada.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Aprobar la solicitud de reinscripción.
     */
    public function approve(int $adminId, ?string $notes = null): void
    {
        $this->update([
            'status' => 'approved',
            'admin_notes' => $notes,
            'processed_by' => $adminId,
            'processed_at' => now(),
        ]);
    }

    /**
     * Rechazar la solicitud de reinscripción.
     */
    public function reject(int $adminId, ?string $notes = null): void
    {
        $this->update([
            'status' => 'rejected',
            'admin_notes' => $notes,
            'processed_by' => $adminId,
            'processed_at' => now(),
        ]);
    }

    /**
     * Cancelar la solicitud por parte del estudiante.
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Scope para solicitudes pendientes.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope para solicitudes de un período académico específico.
     */
    public function scopeForAcademicPeriod($query, int $academicPeriodId)
    {
        return $query->where('target_academic_period_id', $academicPeriodId);
    }

    /**
     * Crear la inscripción efectiva después de aprobar.
     */
    public function createEnrollment(): Enrollment
    {
        return Enrollment::create([
            'student_id' => $this->student_id,
            'section_id' => $this->target_section_id,
            'academic_period_id' => $this->target_academic_period_id,
            'enrollment_date' => now(),
            'status' => 'active',
            'notes' => 'Reinscripción aprobada. ' . ($this->admin_notes ?? ''),
        ]);
    }
}
