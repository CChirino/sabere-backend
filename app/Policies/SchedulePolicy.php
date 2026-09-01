<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;

class SchedulePolicy
{
    private function isStaff(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'director', 'coordinator']);
    }

    public function view(User $user, Schedule $schedule): bool
    {
        if ($this->isStaff($user) || $user->hasRole('teacher')) {
            return true;
        }

        if ($user->hasRole('student')) {
            $enrollment = $user->activeEnrollment();

            return $enrollment && $enrollment->section_id === $schedule->subjectAssignment->section_id;
        }

        if ($user->hasRole('guardian')) {
            $studentIds = $user->students()->pluck('users.id');

            return \App\Models\Enrollment::whereIn('student_id', $studentIds)
                ->where('section_id', $schedule->subjectAssignment->section_id)
                ->where('status', 'active')
                ->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function update(User $user, Schedule $schedule): bool
    {
        return $this->isStaff($user);
    }

    public function delete(User $user, Schedule $schedule): bool
    {
        return $this->isStaff($user);
    }

    public function viewBySection(User $user, int $sectionId): bool
    {
        if ($this->isStaff($user) || $user->hasRole('teacher')) {
            return true;
        }

        if ($user->hasRole('student')) {
            $enrollment = $user->activeEnrollment();

            return $enrollment && $enrollment->section_id === $sectionId;
        }

        if ($user->hasRole('guardian')) {
            $studentIds = $user->students()->pluck('users.id');

            return \App\Models\Enrollment::whereIn('student_id', $studentIds)
                ->where('section_id', $sectionId)
                ->where('status', 'active')
                ->exists();
        }

        return false;
    }

    public function viewByTeacher(User $user, int $teacherId): bool
    {
        if ($this->isStaff($user)) {
            return true;
        }

        if ($user->hasRole('teacher')) {
            return $user->id === $teacherId;
        }

        return false;
    }

    public function viewByStudent(User $user, int $studentId): bool
    {
        return $this->viewBySection($user, optional(\App\Models\User::find($studentId)?->activeEnrollment())->section_id ?? 0);
    }
}
