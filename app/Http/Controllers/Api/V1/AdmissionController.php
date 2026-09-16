<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\StudentApplication;
use App\Services\AdmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdmissionController extends Controller
{
    public function __construct(
        private AdmissionService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', StudentApplication::class);

        $query = StudentApplication::with(['guardians', 'documents', 'academicPeriod', 'grade'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        return response()->json($query->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', StudentApplication::class);

        $data = $request->validate($this->rules());

        $application = $this->service->create($data, $request->user());

        return response()->json($application->load(['guardians', 'documents']), 201);
    }

    public function show(Request $request, StudentApplication $admission): JsonResponse
    {
        $this->authorize('view', $admission);

        return response()->json($admission->load(['guardians', 'documents', 'academicPeriod', 'grade']));
    }

    public function update(Request $request, StudentApplication $admission): JsonResponse
    {
        $this->authorize('update', $admission);

        $data = $request->validate($this->rules(true));

        $application = $this->service->update($admission, $data);

        return response()->json($application->load(['guardians', 'documents']));
    }

    public function approve(Request $request, StudentApplication $admission): JsonResponse
    {
        $this->authorize('approve', $admission);

        $data = $request->validate([
            'section_id' => ['required', 'integer', 'exists:sections,id'],
        ]);

        $enrollment = $this->service->approve($admission, $request->user(), $data);

        return response()->json($enrollment->load(['student', 'section']), 201);
    }

    public function reject(Request $request, StudentApplication $admission): JsonResponse
    {
        $this->authorize('reject', $admission);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $this->service->reject($admission, $request->user(), $data['rejection_reason']);

        return response()->json($admission->fresh());
    }

    public function suggestSections(StudentApplication $admission): JsonResponse
    {
        $this->authorize('view', $admission);

        return response()->json($this->service->suggestSections($admission)->values());
    }

    private function rules(bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return [
            'academic_period_id' => [$required, 'integer', 'exists:academic_periods,id'],
            'grade_id' => [$required, 'integer', 'exists:grades,id'],
            'first_name' => [$required, 'string', 'max:255'],
            'last_name' => [$required, 'string', 'max:255'],
            'birth_date' => [$required, 'date'],
            'gender' => [$required, 'string', 'max:50'],
            'id_number' => [$required, 'string', 'max:50'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'current_school' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'guardians' => [$required, 'array', 'min:1'],
            'guardians.*.first_name' => ['required', 'string', 'max:255'],
            'guardians.*.last_name' => ['required', 'string', 'max:255'],
            'guardians.*.id_number' => ['required', 'string', 'max:50'],
            'guardians.*.email' => ['required', 'email', 'max:255'],
            'guardians.*.phone' => ['required', 'string', 'max:50'],
            'guardians.*.relationship' => ['required', 'string', 'max:100'],
            'guardians.*.is_primary' => ['boolean'],
            'guardians.*.address' => ['nullable', 'string', 'max:500'],
            'documents' => ['nullable', 'array'],
            'documents.*.file' => ['required', 'file', 'max:5120'],
            'documents.*.type' => ['required', 'string', 'max:100'],
            'documents.*.description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
