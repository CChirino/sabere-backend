<?php

namespace App\Services;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubjectService
{
    public function queryForUser(Request $request): Builder
    {
        $query = Subject::with('subjectArea');

        $user = Auth::user();

        if ($user->hasRole('student')) {
            $enrollment = $user->activeEnrollment();
            $query->whereHas('assignments', function ($q) use ($enrollment) {
                $q->where('section_id', $enrollment?->section_id);
            });
        }

        if ($user->hasRole('guardian')) {
            $studentIds = $user->students()->pluck('users.id');
            $sectionIds = \App\Models\Enrollment::whereIn('student_id', $studentIds)
                ->where('status', 'active')
                ->pluck('section_id');
            $query->whereHas('assignments', function ($q) use ($sectionIds) {
                $q->whereIn('section_id', $sectionIds);
            });
        }

        if ($user->hasRole('teacher')) {
            $query->whereHas('assignments', function ($q) use ($user) {
                $q->where('teacher_id', $user->id);
            });
        }

        if ($request->has('subject_area_id')) {
            $query->where('subject_area_id', $request->subject_area_id);
        }

        return $query;
    }

    public function create(Request $request): Subject
    {
        $validated = $request->validate([
            'subject_area_id' => 'required|exists:subject_areas,id',
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:subjects,code',
            'description' => 'nullable|string',
            'status' => 'boolean',
        ]);

        $subject = Subject::create($validated);
        $subject->load('subjectArea');

        return $subject;
    }

    public function update(Subject $subject, Request $request): Subject
    {
        $validated = $request->validate([
            'subject_area_id' => 'required|exists:subject_areas,id',
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:subjects,code,'.$subject->id,
            'description' => 'nullable|string',
            'status' => 'boolean',
        ]);

        $subject->update($validated);
        $subject->load('subjectArea');

        return $subject;
    }

    public function canDestroy(Subject $subject): bool
    {
        return $subject->grades()->count() === 0;
    }

    public function assignToGrade(Subject $subject, Request $request): void
    {
        $validated = $request->validate([
            'grade_id' => 'required|exists:grades,id',
            'school_year' => 'required|string|size:9|regex:/^\d{4}-\d{4}$/',
            'hours_per_week' => 'required|integer|min:1|max:20',
            'is_optional' => 'boolean',
        ]);

        $exists = $subject->grades()
            ->where('grade_id', $validated['grade_id'])
            ->where('school_year', $validated['school_year'])
            ->exists();

        if ($exists) {
            throw new \InvalidArgumentException('La materia ya está asignada a este grado para el año escolar especificado');
        }

        $subject->grades()->attach($validated['grade_id'], [
            'school_year' => $validated['school_year'],
            'hours_per_week' => $validated['hours_per_week'],
            'is_optional' => $validated['is_optional'] ?? false,
            'status' => true,
        ]);
    }

    public function removeFromGrade(Subject $subject, int $gradeId, string $schoolYear): void
    {
        $exists = $subject->grades()
            ->where('grade_id', $gradeId)
            ->where('school_year', $schoolYear)
            ->exists();

        if (! $exists) {
            throw new \InvalidArgumentException('La materia no está asignada a este grado para el año escolar especificado');
        }

        $subject->grades()->wherePivot('grade_id', $gradeId)
            ->wherePivot('school_year', $schoolYear)
            ->detach();
    }

    public function grades(Subject $subject): mixed
    {
        $subject->load('grades');

        return $subject->grades->map(function ($grade) {
            return [
                'id' => $grade->id,
                'name' => $grade->name,
                'education_level' => $grade->educationLevel->name,
                'school_year' => $grade->pivot->school_year,
                'hours_per_week' => $grade->pivot->hours_per_week,
                'is_optional' => $grade->pivot->is_optional,
                'status' => $grade->pivot->status,
            ];
        });
    }
}
