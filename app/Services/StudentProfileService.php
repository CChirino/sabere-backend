<?php

namespace App\Services;

use App\Models\StudentDocument;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StudentProfileService
{
    public function updateOrCreate(User $student, array $data): StudentProfile
    {
        return StudentProfile::updateOrCreate(
            ['user_id' => $student->id],
            [
                'blood_type' => $data['blood_type'] ?? null,
                'allergies' => $data['allergies'] ?? null,
                'medical_conditions' => $data['medical_conditions'] ?? null,
                'medications' => $data['medications'] ?? null,
                'dietary_restrictions' => $data['dietary_restrictions'] ?? null,
                'emergency_contact_name' => $data['emergency_contact_name'],
                'emergency_contact_phone' => $data['emergency_contact_phone'],
                'emergency_contact_relationship' => $data['emergency_contact_relationship'] ?? null,
                'authorized_pickup' => $data['authorized_pickup'] ?? [],
                'additional_notes' => $data['additional_notes'] ?? null,
            ]
        );
    }

    public function storeDocument(User $student, array $data, ?User $uploader = null): StudentDocument
    {
        /** @var UploadedFile $file */
        $file = $data['file'];
        $path = $file->store('students/'.$student->id.'/documents', 'public');

        return StudentDocument::create([
            'user_id' => $student->id,
            'type' => $data['type'],
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'description' => $data['description'] ?? null,
            'is_verified' => false,
        ]);
    }

    public function deleteDocument(StudentDocument $document): void
    {
        Storage::disk('public')->delete($document->path);
        $document->delete();
    }
}
