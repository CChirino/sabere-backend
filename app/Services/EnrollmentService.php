<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnrollmentService
{
    public function queryForUser(Request $request): Builder
    {
        $query = Enrollment::with(['student', 'section.grade.educationLevel', 'academicPeriod']);

        $user = Auth::user();

        if ($user->hasRole('student')) {
            $query->where('student_id', $user->id);
        }

        if ($user->hasRole('guardian')) {
            $studentIds = $user->students()->pluck('users.id');
            $query->whereIn('student_id', $studentIds);
        }

        if ($user->hasRole('teacher')) {
            $sectionIds = $user->subjectAssignments()->pluck('section_id');
            $query->whereIn('section_id', $sectionIds);
        }

        if ($request->has('academic_period_id')) {
            $query->where('academic_period_id', $request->academic_period_id);
        }

        if ($request->has('section_id')) {
            $query->where('section_id', $request->section_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        return $query;
    }

    public function create(Request $request): Enrollment
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'section_id' => 'required|exists:sections,id',
            'academic_period_id' => 'required|exists:academic_periods,id',
            'enrollment_date' => 'required|date',
            'status' => 'in:active,inactive,transferred,graduated,withdrawn',
            'notes' => 'nullable|string',
        ]);

        $student = User::findOrFail($validated['student_id']);

        if (! $student->hasRole('student')) {
            throw new \InvalidArgumentException('El usuario seleccionado no tiene rol de estudiante');
        }

        $exists = Enrollment::where('student_id', $validated['student_id'])
            ->where('academic_period_id', $validated['academic_period_id'])
            ->exists();

        if ($exists) {
            throw new \InvalidArgumentException('El estudiante ya tiene una inscripción para este período académico');
        }

        $this->validateSectionCapacity($validated['section_id']);

        $enrollment = Enrollment::create([
            'student_id' => $validated['student_id'],
            'section_id' => $validated['section_id'],
            'academic_period_id' => $validated['academic_period_id'],
            'enrollment_date' => $validated['enrollment_date'],
            'status' => $validated['status'] ?? 'active',
            'notes' => $validated['notes'] ?? null,
        ]);

        $enrollment->load(['student', 'section.grade.educationLevel', 'academicPeriod']);

        return $enrollment;
    }

    public function update(Enrollment $enrollment, Request $request): Enrollment
    {
        $validated = $request->validate([
            'section_id' => 'sometimes|exists:sections,id',
            'status' => 'sometimes|in:active,inactive,transferred,graduated,withdrawn',
            'notes' => 'nullable|string',
        ]);

        if (isset($validated['section_id']) && $validated['section_id'] != $enrollment->section_id) {
            $this->validateSectionCapacity($validated['section_id']);
        }

        $enrollment->update([
            'section_id' => $validated['section_id'] ?? $enrollment->section_id,
            'status' => $validated['status'] ?? $enrollment->status,
            'notes' => array_key_exists('notes', $validated) ? $validated['notes'] : $enrollment->notes,
        ]);

        $enrollment->load(['student', 'section.grade.educationLevel', 'academicPeriod']);

        return $enrollment;
    }

    public function transfer(Enrollment $enrollment, Request $request): Enrollment
    {
        $validated = $request->validate([
            'new_section_id' => 'required|exists:sections,id',
            'notes' => 'nullable|string',
        ]);

        $this->validateSectionCapacity($validated['new_section_id']);

        $enrollment->update([
            'section_id' => $validated['new_section_id'],
            'notes' => $validated['notes'] ?? $enrollment->notes,
        ]);

        $enrollment->load(['student', 'section.grade.educationLevel', 'academicPeriod']);

        return $enrollment;
    }

    public function byStudent(int $studentId): mixed
    {
        $student = User::find($studentId);

        if (is_null($student) || ! $student->hasRole('student')) {
            throw new \InvalidArgumentException('Estudiante no encontrado');
        }

        return Enrollment::with(['section.grade.educationLevel', 'academicPeriod'])
            ->where('student_id', $studentId)
            ->orderBy('enrollment_date', 'desc')
            ->get();
    }

    private function validateSectionCapacity(int $sectionId): void
    {
        $section = Section::findOrFail($sectionId);

        if ($section->capacity) {
            $currentCount = Enrollment::where('section_id', $sectionId)
                ->where('status', 'active')
                ->count();

            if ($currentCount >= $section->capacity) {
                throw new \InvalidArgumentException('La sección ha alcanzado su capacidad máxima');
            }
        }
    }
}
