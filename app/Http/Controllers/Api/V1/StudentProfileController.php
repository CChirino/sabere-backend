<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\StudentDocument;
use App\Models\User;
use App\Services\StudentProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class StudentProfileController extends Controller
{
    public function __construct(
        private StudentProfileService $service
    ) {}

    public function show(Request $request, User $student): JsonResponse
    {
        Gate::authorize('view-profile', $student);

        return response()->json([
            'profile' => $student->studentProfile ?? $this->createEmpty($student),
            'documents' => $student->studentDocuments,
        ]);
    }

    public function update(Request $request, User $student): JsonResponse
    {
        Gate::authorize('update-profile', $student);

        $data = $request->validate([
            'blood_type' => ['nullable', 'string', 'max:10'],
            'allergies' => ['nullable', 'string', 'max:2000'],
            'medical_conditions' => ['nullable', 'string', 'max:2000'],
            'medications' => ['nullable', 'string', 'max:2000'],
            'dietary_restrictions' => ['nullable', 'string', 'max:2000'],
            'emergency_contact_name' => ['required', 'string', 'max:255'],
            'emergency_contact_phone' => ['required', 'string', 'max:50'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:100'],
            'authorized_pickup' => ['nullable', 'array'],
            'authorized_pickup.*.name' => ['required_with:authorized_pickup', 'string', 'max:255'],
            'authorized_pickup.*.id_number' => ['nullable', 'string', 'max:50'],
            'authorized_pickup.*.relationship' => ['nullable', 'string', 'max:100'],
            'authorized_pickup.*.phone' => ['nullable', 'string', 'max:50'],
            'additional_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $profile = $this->service->updateOrCreate($student, $data);

        return response()->json($profile);
    }

    public function storeDocument(Request $request, User $student): JsonResponse
    {
        $this->authorize('create', [StudentDocument::class, $student]);

        $data = $request->validate([
            'file' => ['required', 'file', 'max:5120'],
            'type' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $document = $this->service->storeDocument($student, $data, $request->user());

        return response()->json($document, 201);
    }

    public function destroyDocument(Request $request, User $student, StudentDocument $document): JsonResponse
    {
        $this->authorize('delete', $document);

        $this->service->deleteDocument($document);

        return response()->json(['message' => 'Documento eliminado.']);
    }

    public function verifyDocument(Request $request, User $student, StudentDocument $document): JsonResponse
    {
        $this->authorize('verify', $document);

        $document->markAsVerified($request->user());

        return response()->json($document);
    }

    private function createEmpty(User $student): array
    {
        return [
            'user_id' => $student->id,
            'blood_type' => null,
            'allergies' => null,
            'medical_conditions' => null,
            'medications' => null,
            'dietary_restrictions' => null,
            'emergency_contact_name' => null,
            'emergency_contact_phone' => null,
            'emergency_contact_relationship' => null,
            'authorized_pickup' => [],
            'additional_notes' => null,
        ];
    }
}
