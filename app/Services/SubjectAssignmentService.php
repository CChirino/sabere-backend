<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubjectAssignmentService
{
    public function queryForUser(Request $request): Builder
    {
        $query = SubjectAssignment::with(['teacher', 'subject', 'section.grade', 'academicPeriod']);

        $user = Auth::user();

        if ($user->hasRole('student')) {
            $enrollment = $user->activeEnrollment();
            $query->where('section_id', $enrollment?->section_id);
        }

        if ($user->hasRole('guardian')) {
            $studentIds = $user->students()->pluck('users.id');
            $sectionIds = Enrollment::whereIn('student_id', $studentIds)
                ->where('status', 'active')
                ->pluck('section_id');
            $query->whereIn('section_id', $sectionIds);
        }

        if ($user->hasRole('teacher')) {
            $query->where('teacher_id', $user->id);
        }

        if ($request->has('academic_period_id')) {
            $query->where('academic_period_id', $request->academic_period_id);
        }

        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        if ($request->has('section_id')) {
            $query->where('section_id', $request->section_id);
        }

        if ($request->has('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        return $query;
    }

    public function create(Request $request): SubjectAssignment
    {
        $validated = $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'subject_id' => 'required|exists:subjects,id',
            'section_id' => 'required|exists:sections,id',
            'academic_period_id' => 'required|exists:academic_periods,id',
            'status' => 'boolean',
        ]);

        $teacher = User::findOrFail($validated['teacher_id']);

        if (! $teacher->hasRole('teacher')) {
            throw new \InvalidArgumentException('El usuario seleccionado no tiene rol de profesor');
        }

        $exists = SubjectAssignment::where('subject_id', $validated['subject_id'])
            ->where('section_id', $validated['section_id'])
            ->where('academic_period_id', $validated['academic_period_id'])
            ->exists();

        if ($exists) {
            throw new \InvalidArgumentException('Ya existe una asignación para esta materia en esta sección y período');
        }

        $assignment = SubjectAssignment::create($validated);
        $assignment->load(['teacher', 'subject', 'section.grade', 'academicPeriod']);

        return $assignment;
    }

    public function update(SubjectAssignment $assignment, Request $request): SubjectAssignment
    {
        $validated = $request->validate([
            'teacher_id' => 'sometimes|exists:users,id',
            'status' => 'boolean',
        ]);

        if (isset($validated['teacher_id'])) {
            $teacher = User::findOrFail($validated['teacher_id']);

            if (! $teacher->hasRole('teacher')) {
                throw new \InvalidArgumentException('El usuario seleccionado no tiene rol de profesor');
            }
        }

        $assignment->update($validated);
        $assignment->load(['teacher', 'subject', 'section.grade', 'academicPeriod']);

        return $assignment;
    }

    public function canDestroy(SubjectAssignment $assignment): bool
    {
        return ! $assignment->tasks()->exists() && ! $assignment->studentScores()->exists();
    }

    public function byTeacher(int $teacherId): mixed
    {
        return SubjectAssignment::with(['subject', 'section.grade.educationLevel', 'academicPeriod'])
            ->where('teacher_id', $teacherId)
            ->where('status', true)
            ->get();
    }

    public function students(SubjectAssignment $assignment): mixed
    {
        return $assignment->section->enrollments()
            ->with('student')
            ->where('status', 'active')
            ->get()
            ->pluck('student');
    }
}
