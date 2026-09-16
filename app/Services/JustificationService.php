<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Justification;
use App\Models\User;

class JustificationService
{
    /**
     * Crear una solicitud de justificativo.
     */
    public function create(array $data, User $guardian): Justification
    {
        $data['guardian_id'] = $guardian->id;
        $data['status'] = 'pending';

        $justification = Justification::create($data);

        NotificationService::notifyJustification($justification, 'submitted');

        return $justification;
    }

    /**
     * Aprobar un justificativo y marcar asistencias como excusadas.
     */
    public function approve(Justification $justification, User $reviewer, ?string $notes = null): Justification
    {
        $justification->update([
            'status' => 'approved',
            'reviewed_by' => $reviewer->id,
            'review_notes' => $notes,
            'reviewed_at' => now(),
        ]);

        Attendance::where('student_id', $justification->student_id)
            ->where('academic_period_id', $justification->academic_period_id)
            ->whereBetween('date', [$justification->start_date, $justification->end_date])
            ->where('status', Attendance::STATUS_ABSENT)
            ->update(['status' => Attendance::STATUS_EXCUSED]);

        NotificationService::notifyJustification($justification, 'approved');

        return $justification->fresh();
    }

    /**
     * Rechazar un justificativo.
     */
    public function reject(Justification $justification, User $reviewer, ?string $notes = null): Justification
    {
        $justification->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewer->id,
            'review_notes' => $notes,
            'reviewed_at' => now(),
        ]);

        NotificationService::notifyJustification($justification, 'rejected');

        return $justification->fresh();
    }
}
