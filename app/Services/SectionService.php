<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SectionService
{
    public function queryForUser(Request $request): Builder
    {
        $query = Section::with(['grade.educationLevel', 'academicPeriod'])
            ->withCount('enrollments');

        $user = Auth::user();

        if ($user->hasRole('student')) {
            $enrollment = $user->activeEnrollment();
            $query->where('id', $enrollment?->section_id);
        }

        if ($user->hasRole('guardian')) {
            $studentIds = $user->students()->pluck('users.id');
            $sectionIds = Enrollment::whereIn('student_id', $studentIds)
                ->where('status', 'active')
                ->pluck('section_id');
            $query->whereIn('id', $sectionIds);
        }

        if ($user->hasRole('teacher')) {
            $sectionIds = $user->subjectAssignments()->pluck('section_id');
            $query->whereIn('id', $sectionIds);
        }

        if ($request->has('academic_period_id')) {
            $query->where('academic_period_id', $request->academic_period_id);
        }

        if ($request->has('grade_id')) {
            $query->where('grade_id', $request->grade_id);
        }

        if ($request->has('education_level_id')) {
            $query->whereHas('grade', function ($q) use ($request) {
                $q->where('education_level_id', $request->education_level_id);
            });
        }

        return $query->orderBy('name');
    }

    public function create(Request $request): Section
    {
        $validated = $request->validate([
            'grade_id' => 'required|exists:grades,id',
            'academic_period_id' => 'required|exists:academic_periods,id',
            'name' => 'required|string|max:10',
            'capacity' => 'nullable|integer|min:1|max:100',
            'status' => 'boolean',
        ]);

        $exists = Section::where('grade_id', $validated['grade_id'])
            ->where('academic_period_id', $validated['academic_period_id'])
            ->where('name', $validated['name'])
            ->exists();

        if ($exists) {
            throw new \InvalidArgumentException('Ya existe una sección con este nombre para el grado y período especificado');
        }

        $section = Section::create($validated);
        $section->load(['grade.educationLevel', 'academicPeriod']);

        return $section;
    }

    public function update(Section $section, Request $request): Section
    {
        $validated = $request->validate([
            'grade_id' => 'required|exists:grades,id',
            'academic_period_id' => 'required|exists:academic_periods,id',
            'name' => 'required|string|max:10',
            'capacity' => 'nullable|integer|min:1|max:100',
            'status' => 'boolean',
        ]);

        $exists = Section::where('grade_id', $validated['grade_id'])
            ->where('academic_period_id', $validated['academic_period_id'])
            ->where('name', $validated['name'])
            ->where('id', '!=', $section->id)
            ->exists();

        if ($exists) {
            throw new \InvalidArgumentException('Ya existe otra sección con este nombre para el grado y período especificado');
        }

        $section->update($validated);
        $section->load(['grade.educationLevel', 'academicPeriod']);

        return $section;
    }

    public function canDestroy(Section $section): bool
    {
        return ! $section->enrollments()->exists();
    }

    public function students(Section $section): mixed
    {
        return $section->enrollments()
            ->with('student')
            ->where('status', 'active')
            ->get()
            ->pluck('student');
    }

    public function subjects(Section $section): mixed
    {
        return $section->subjectAssignments()
            ->with(['subject', 'teacher'])
            ->where('status', true)
            ->get();
    }
}
