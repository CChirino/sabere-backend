<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Section;
use App\Models\StudentApplication;
use App\Models\StudentApplicationDocument;
use App\Models\StudentDocument;
use App\Models\StudentGuardian;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdmissionService
{
    public function create(array $data, User $createdBy): StudentApplication
    {
        return DB::transaction(function () use ($data, $createdBy) {
            $application = StudentApplication::create([
                'academic_period_id' => $data['academic_period_id'],
                'grade_id' => $data['grade_id'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'birth_date' => $data['birth_date'],
                'gender' => $data['gender'],
                'id_number' => $data['id_number'],
                'nationality' => $data['nationality'] ?? null,
                'address' => $data['address'] ?? null,
                'current_school' => $data['current_school'] ?? null,
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
                'created_by' => $createdBy->id,
            ]);

            foreach ($data['guardians'] as $guardian) {
                $application->guardians()->create($guardian);
            }

            if (! empty($data['documents'])) {
                foreach ($data['documents'] as $doc) {
                    $this->storeApplicationDocument($application, $doc);
                }
            }

            return $application;
        });
    }

    public function update(StudentApplication $application, array $data): StudentApplication
    {
        return DB::transaction(function () use ($application, $data) {
            $application->update([
                'academic_period_id' => $data['academic_period_id'] ?? $application->academic_period_id,
                'grade_id' => $data['grade_id'] ?? $application->grade_id,
                'first_name' => $data['first_name'] ?? $application->first_name,
                'last_name' => $data['last_name'] ?? $application->last_name,
                'birth_date' => $data['birth_date'] ?? $application->birth_date,
                'gender' => $data['gender'] ?? $application->gender,
                'id_number' => $data['id_number'] ?? $application->id_number,
                'nationality' => $data['nationality'] ?? $application->nationality,
                'address' => $data['address'] ?? $application->address,
                'current_school' => $data['current_school'] ?? $application->current_school,
                'notes' => $data['notes'] ?? $application->notes,
            ]);

            return $application;
        });
    }

    public function approve(StudentApplication $application, User $processor, array $data): Enrollment
    {
        return DB::transaction(function () use ($application, $processor, $data) {
            $academicPeriod = $application->academicPeriod;

            // Crear usuario estudiante
            $student = User::create([
                'name' => trim("{$application->first_name} {$application->last_name}"),
                'email' => $this->generateUniqueEmail($application->first_name, $application->last_name, $application->id_number),
                'password' => Hash::make(Str::random(16)),
                'birth_date' => $application->birth_date,
            ]);
            $student->assignRole('student');

            // Crear/vincular representantes
            foreach ($application->guardians as $guardianData) {
                $guardian = User::where('email', $guardianData->email)->first()
                    ?? User::create([
                        'name' => trim("{$guardianData->first_name} {$guardianData->last_name}"),
                        'email' => $guardianData->email,
                        'phone' => $guardianData->phone,
                        'password' => Hash::make(Str::random(16)),
                    ]);

                if (! $guardian->hasRole('guardian')) {
                    $guardian->assignRole('guardian');
                }

                StudentGuardian::firstOrCreate(
                    ['student_id' => $student->id, 'guardian_id' => $guardian->id],
                    [
                        'relationship' => $guardianData->relationship,
                        'is_primary' => $guardianData->is_primary,
                        'emergency_contact' => $guardianData->is_primary,
                        'can_pickup' => true,
                        'phone' => $guardianData->phone,
                        'status' => true,
                    ]
                );
            }

            // Crear matrícula
            $enrollment = Enrollment::create([
                'academic_period_id' => $academicPeriod->id,
                'student_id' => $student->id,
                'section_id' => $data['section_id'],
                'enrollment_date' => now(),
                'status' => 'active',
                'notes' => $application->notes,
            ]);

            // Copiar documentos a ficha del estudiante
            foreach ($application->documents as $doc) {
                StudentDocument::create([
                    'user_id' => $student->id,
                    'type' => $doc->type,
                    'path' => $doc->path,
                    'original_name' => $doc->original_name,
                    'mime' => $doc->mime,
                    'description' => $doc->description,
                    'is_verified' => false,
                ]);
            }

            // Crear ficha vacía
            StudentProfile::create(['user_id' => $student->id]);

            $application->update([
                'status' => 'approved',
                'processed_by' => $processor->id,
                'processed_at' => now(),
            ]);

            return $enrollment;
        });
    }

    public function reject(StudentApplication $application, User $processor, string $reason): void
    {
        $application->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'processed_by' => $processor->id,
            'processed_at' => now(),
        ]);
    }

    /**
     * Sugerir secciones del grado solicitado con cupo disponible.
     */
    public function suggestSections(StudentApplication $application): Collection
    {
        return Section::where('academic_period_id', $application->academic_period_id)
            ->where('grade_id', $application->grade_id)
            ->withCount(['enrollments' => function ($query) {
                $query->where('status', 'active');
            }])
            ->get()
            ->filter(fn (Section $section) => $section->capacity === null || $section->enrollments_count < $section->capacity)
            ->values();
    }

    private function storeApplicationDocument(StudentApplication $application, array $doc): StudentApplicationDocument
    {
        /** @var UploadedFile $file */
        $file = $doc['file'];
        $path = $file->store('admissions/'.$application->id, 'public');

        return $application->documents()->create([
            'type' => $doc['type'],
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'description' => $doc['description'] ?? null,
        ]);
    }

    private function generateUniqueEmail(string $firstName, string $lastName, string $idNumber): string
    {
        $base = Str::slug($firstName, '').'.'.Str::slug($lastName, '');

        return $base.'.'.$idNumber.'@sabere.local';
    }
}
