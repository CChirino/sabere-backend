<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Justification;
use App\Services\JustificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JustificationController extends Controller
{
    public function __construct(private JustificationService $justificationService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Justification::class);

        $query = Justification::with(['student', 'guardian', 'academicPeriod', 'reviewedBy']);
        $user = $request->user();

        if ($user->hasRole(['admin', 'director', 'coordinator'])) {
            // staff ve todo
        } elseif ($user->hasRole('guardian')) {
            $studentIds = $user->students()->pluck('users.id');
            $query->where(fn ($q) => $q->where('guardian_id', $user->id)->orWhereIn('student_id', $studentIds));
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return response()->json($query->paginate(15));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Justification::class);

        $data = $request->validate([
            'student_id' => ['required', 'exists:users,id'],
            'academic_period_id' => ['required', 'exists:academic_periods,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string'],
            'document_path' => ['nullable', 'string'],
        ]);

        $justification = $this->justificationService->create($data, $request->user());

        return response()->json($justification->load(['student', 'guardian', 'academicPeriod']), 201);
    }

    public function show(Justification $justification): JsonResponse
    {
        $this->authorize('view', $justification);

        return response()->json($justification->load(['student', 'guardian', 'academicPeriod', 'reviewedBy']));
    }

    public function approve(Request $request, Justification $justification): JsonResponse
    {
        $this->authorize('review', $justification);

        $data = $request->validate(['notes' => ['nullable', 'string']]);

        $justification = $this->justificationService->approve($justification, $request->user(), $data['notes'] ?? null);

        return response()->json($justification->load(['student', 'guardian', 'academicPeriod', 'reviewedBy']));
    }

    public function reject(Request $request, Justification $justification): JsonResponse
    {
        $this->authorize('review', $justification);

        $data = $request->validate(['notes' => ['nullable', 'string']]);

        $justification = $this->justificationService->reject($justification, $request->user(), $data['notes'] ?? null);

        return response()->json($justification->load(['student', 'guardian', 'academicPeriod', 'reviewedBy']));
    }

    public function destroy(Justification $justification): JsonResponse
    {
        $this->authorize('delete', $justification);

        $justification->delete();

        return response()->json(['message' => 'Justificativo eliminado']);
    }
}
