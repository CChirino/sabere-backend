<?php

namespace App\Services;

use App\Models\DisciplinaryRecord;
use App\Models\User;

class DisciplineService
{
    /**
     * Crear una incidencia disciplinaria.
     */
    public function create(array $data, User $recordedBy): DisciplinaryRecord
    {
        $data['recorded_by'] = $recordedBy->id;

        $record = DisciplinaryRecord::create($data);

        NotificationService::notifyDisciplinaryRecord($record);

        return $record;
    }

    /**
     * Actualizar una incidencia existente.
     */
    public function update(DisciplinaryRecord $record, array $data): DisciplinaryRecord
    {
        $record->update($data);

        return $record->fresh();
    }
}
