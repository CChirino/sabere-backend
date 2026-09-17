<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DisciplinaryRecord;
use App\Services\DisciplineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DisciplinaryRecordController extends Controller
{
    public function __construct(private DisciplineService $disciplineService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DisciplinaryRecord::class);

        $query = DisciplinaryRecord::with(['student', 'incidentType', 'recordedBy']);

        $user = $request->user();

        if ($user->hasRole(['admin', 'director', 'coordinator'])) {
            // staff ve todo
        } elseif ($user->hasRole('teacher')) {
            $sectionIds = $user->subjectAssignments()->pluck('section_id');
            $query->where(fn ($q) => $q->whereIn('section_id', $sectionIds)->orWhere('recorded_by', $user->id));
        } elseif ($user->hasRole('student')) {
            $query->where('student_id', $user->id)->where('is_private', false);
        } elseif ($user->hasRole('guardian')) {
            $studentIds = $user->students()->pluck('users.id');
            $query->whereIn('student_id', $studentIds)->where('is_private', false);
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->input('severity'));
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->input('student_id'));
        }

        return $this->sendPaginatedResponse(
            $query->paginate($this->perPage($request)),
            'Incidencias obtenidas exitosamente'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', DisciplinaryRecord::class);

        $data = $request->validate([
            'student_id' => ['required', 'exists:users,id'],
            'section_id' => ['nullable', 'exists:sections,id'],
            'academic_period_id' => ['required', 'exists:academic_periods,id'],
            'incident_type_id' => ['required', 'exists:incident_types,id'],
            'severity' => ['required', 'in:leve,moderada,grave'],
            'description' => ['required', 'string'],
            'action_taken' => ['nullable', 'string'],
            'date' => ['required', 'date'],
            'is_private' => ['boolean'],
        ]);

        $record = $this->disciplineService->create($data, $request->user());

        return $this->sendResponse(
            $record->load(['student', 'incidentType', 'recordedBy']),
            'Incidencia creada exitosamente',
            201
        );
    }

    public function show(DisciplinaryRecord $disciplinaryRecord): JsonResponse
    {
        $this->authorize('view', $disciplinaryRecord);

        return $this->sendResponse(
            $disciplinaryRecord->load(['student', 'incidentType', 'recordedBy']),
            'Incidencia obtenida exitosamente'
        );
    }

    public function update(Request $request, DisciplinaryRecord $disciplinaryRecord): JsonResponse
    {
        $this->authorize('update', $disciplinaryRecord);

        $data = $request->validate([
            'incident_type_id' => ['sometimes', 'required', 'exists:incident_types,id'],
            'severity' => ['sometimes', 'required', 'in:leve,moderada,grave'],
            'description' => ['sometimes', 'required', 'string'],
            'action_taken' => ['nullable', 'string'],
            'date' => ['sometimes', 'required', 'date'],
            'is_private' => ['boolean'],
        ]);

        $record = $this->disciplineService->update($disciplinaryRecord, $data);

        return $this->sendResponse(
            $record->load(['student', 'incidentType', 'recordedBy']),
            'Incidencia actualizada exitosamente'
        );
    }

    public function destroy(DisciplinaryRecord $disciplinaryRecord): JsonResponse
    {
        $this->authorize('delete', $disciplinaryRecord);

        $disciplinaryRecord->delete();

        return $this->sendResponse(null, 'Incidencia eliminada exitosamente');
    }
}
