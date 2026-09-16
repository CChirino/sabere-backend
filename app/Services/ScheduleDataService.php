<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScheduleDataService
{
    public function index(Request $request): mixed
    {
        $query = Schedule::with([
            'subjectAssignment.subject',
            'subjectAssignment.teacher',
            'subjectAssignment.section.grade',
        ]);

        $user = Auth::user();

        if ($user->hasRole('student')) {
            $enrollment = $user->activeEnrollment();
            $query->whereHas('subjectAssignment', function ($q) use ($enrollment) {
                $q->where('section_id', $enrollment?->section_id);
            });
        }

        if ($user->hasRole('guardian')) {
            $studentIds = $user->students()->pluck('users.id');
            $sectionIds = \App\Models\Enrollment::whereIn('student_id', $studentIds)
                ->where('status', 'active')
                ->pluck('section_id');
            $query->whereHas('subjectAssignment', function ($q) use ($sectionIds) {
                $q->whereIn('section_id', $sectionIds);
            });
        }

        if ($user->hasRole('teacher')) {
            $query->whereHas('subjectAssignment', function ($q) use ($user) {
                $q->where('teacher_id', $user->id);
            });
        }

        $this->applyFilters($query, $request);

        $perPage = app(PaginationService::class)->perPage($request);

        return $query->orderBy('day_of_week')
            ->orderBy('start_time')
            ->paginate($perPage);
    }

    public function weeklyForSection(Section $section): array
    {
        $schedules = Schedule::with(['subjectAssignment.subject', 'subjectAssignment.teacher'])
            ->whereHas('subjectAssignment', function ($q) use ($section) {
                $q->where('section_id', $section->id)
                    ->where('status', true);
            })
            ->where('status', true)
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');

        return [
            'section' => $section->load('grade.educationLevel'),
            'schedule' => $this->orderByDays($schedules),
            'days' => Schedule::DAYS,
        ];
    }

    public function weeklyForTeacher(User $teacher): array
    {
        $schedules = Schedule::with([
            'subjectAssignment.subject',
            'subjectAssignment.section.grade.educationLevel',
        ])
            ->whereHas('subjectAssignment', function ($q) use ($teacher) {
                $q->where('teacher_id', $teacher->id)
                    ->where('status', true);
            })
            ->where('status', true)
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');

        return [
            'teacher' => $teacher,
            'schedule' => $this->orderByDays($schedules),
            'days' => Schedule::DAYS,
        ];
    }

    public function todayForSection(Section $section, string $today): array
    {
        $schedules = Schedule::with(['subjectAssignment.subject', 'subjectAssignment.teacher'])
            ->whereHas('subjectAssignment', function ($q) use ($section) {
                $q->where('section_id', $section->id)
                    ->where('status', true);
            })
            ->where('day_of_week', $today)
            ->where('status', true)
            ->orderBy('start_time')
            ->get();

        return [
            'section' => $section->load('grade.educationLevel'),
            'day' => $today,
            'day_name' => Schedule::DAYS[$today] ?? $today,
            'schedules' => $schedules,
        ];
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        if ($request->has('subject_assignment_id')) {
            $query->where('subject_assignment_id', $request->subject_assignment_id);
        }

        if ($request->has('day_of_week')) {
            $query->where('day_of_week', $request->day_of_week);
        }

        if ($request->has('section_id')) {
            $query->whereHas('subjectAssignment', function ($q) use ($request) {
                $q->where('section_id', $request->section_id);
            });
        }

        if ($request->has('teacher_id')) {
            $query->whereHas('subjectAssignment', function ($q) use ($request) {
                $q->where('teacher_id', $request->teacher_id);
            });
        }

        if ($request->has('academic_period_id')) {
            $query->whereHas('subjectAssignment', function ($q) use ($request) {
                $q->where('academic_period_id', $request->academic_period_id);
            });
        }
    }

    private function orderByDays($schedules): array
    {
        $orderedDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

        return collect($orderedDays)->mapWithKeys(function ($day) use ($schedules) {
            return [$day => $schedules->get($day, collect())];
        })->toArray();
    }
}
