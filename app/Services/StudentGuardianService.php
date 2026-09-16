<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\StudentGuardian;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentGuardianService
{
    public function queryForUser(Request $request): Builder
    {
        $query = StudentGuardian::with(['guardian', 'student']);

        $user = Auth::user();

        if ($user->hasRole('student')) {
            $query->where('student_id', $user->id);
        }

        if ($user->hasRole('guardian')) {
            $query->where('guardian_id', $user->id);
        }

        if ($user->hasRole('teacher')) {
            $sectionIds = $user->subjectAssignments()->pluck('section_id');
            $studentIds = Enrollment::whereIn('section_id', $sectionIds)
                ->where('status', 'active')
                ->pluck('student_id');
            $query->whereIn('student_id', $studentIds);
        }

        if ($request->has('guardian_id')) {
            $query->where('guardian_id', $request->guardian_id);
        }

        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        return $query;
    }

    public function create(Request $request): StudentGuardian
    {
        $validated = $request->validate([
            'guardian_id' => 'required|exists:users,id',
            'student_id' => 'required|exists:users,id',
            'relationship' => 'required|in:father,mother,guardian,grandparent,sibling,other',
            'is_primary' => 'boolean',
            'can_pickup' => 'boolean',
            'emergency_contact' => 'boolean',
            'phone' => 'nullable|string|max:20',
            'status' => 'boolean',
        ]);

        $guardian = User::findOrFail($validated['guardian_id']);

        if (! $guardian->hasRole('guardian')) {
            throw new \InvalidArgumentException('El usuario seleccionado no tiene rol de representante');
        }

        $student = User::findOrFail($validated['student_id']);

        if (! $student->hasRole('student')) {
            throw new \InvalidArgumentException('El usuario seleccionado no tiene rol de estudiante');
        }

        $exists = StudentGuardian::where('guardian_id', $validated['guardian_id'])
            ->where('student_id', $validated['student_id'])
            ->exists();

        if ($exists) {
            throw new \InvalidArgumentException('Ya existe una relación entre este representante y estudiante');
        }

        if ($validated['is_primary'] ?? false) {
            StudentGuardian::where('student_id', $validated['student_id'])
                ->update(['is_primary' => false]);
        }

        $relation = StudentGuardian::create([
            'guardian_id' => $validated['guardian_id'],
            'student_id' => $validated['student_id'],
            'relationship' => $validated['relationship'],
            'is_primary' => $validated['is_primary'] ?? false,
            'can_pickup' => $validated['can_pickup'] ?? false,
            'emergency_contact' => $validated['emergency_contact'] ?? false,
            'phone' => $validated['phone'] ?? null,
            'status' => $validated['status'] ?? true,
        ]);

        $relation->load(['guardian', 'student']);

        return $relation;
    }

    public function update(StudentGuardian $relation, Request $request): StudentGuardian
    {
        $validated = $request->validate([
            'relationship' => 'sometimes|in:father,mother,guardian,grandparent,sibling,other',
            'is_primary' => 'boolean',
            'can_pickup' => 'boolean',
            'emergency_contact' => 'boolean',
            'phone' => 'nullable|string|max:20',
            'status' => 'boolean',
        ]);

        if (isset($validated['is_primary']) && $validated['is_primary']) {
            StudentGuardian::where('student_id', $relation->student_id)
                ->where('id', '!=', $relation->id)
                ->update(['is_primary' => false]);
        }

        $relation->update([
            'relationship' => $validated['relationship'] ?? $relation->relationship,
            'is_primary' => $validated['is_primary'] ?? $relation->is_primary,
            'can_pickup' => $validated['can_pickup'] ?? $relation->can_pickup,
            'emergency_contact' => $validated['emergency_contact'] ?? $relation->emergency_contact,
            'phone' => array_key_exists('phone', $validated) ? $validated['phone'] : $relation->phone,
            'status' => $validated['status'] ?? $relation->status,
        ]);

        $relation->load(['guardian', 'student']);

        return $relation;
    }

    public function studentsByGuardian(int $guardianId): array
    {
        $guardian = User::find($guardianId);

        if (is_null($guardian) || ! $guardian->hasRole('guardian')) {
            throw new \InvalidArgumentException('Representante no encontrado');
        }

        return [
            'students' => $guardian->students()
                ->with(['enrollments' => function ($q) {
                    $q->where('status', 'active')
                        ->with('section.grade.educationLevel');
                }])
                ->get(),
            'message' => 'Estudiantes del representante obtenidos exitosamente',
        ];
    }

    public function guardiansByStudent(int $studentId): array
    {
        $student = User::find($studentId);

        if (is_null($student) || ! $student->hasRole('student')) {
            throw new \InvalidArgumentException('Estudiante no encontrado');
        }

        return [
            'guardians' => $student->guardians()->get(),
            'message' => 'Representantes del estudiante obtenidos exitosamente',
        ];
    }

    public function studentInfo(int $studentId): User
    {
        $student = User::with([
            'enrollments' => function ($q) {
                $q->where('status', 'active')
                    ->with(['section.grade.educationLevel', 'academicPeriod']);
            },
            'scores' => function ($q) {
                $q->with(['subjectAssignment.subject', 'term'])
                    ->orderBy('term_id');
            },
            'taskSubmissions' => function ($q) {
                $q->with(['task.subjectAssignment.subject'])
                    ->orderBy('submitted_at', 'desc')
                    ->limit(10);
            },
        ])->find($studentId);

        if (is_null($student)) {
            throw new \InvalidArgumentException('Estudiante no encontrado');
        }

        return $student;
    }
}
