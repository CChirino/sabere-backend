<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    private function isStaff(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'director', 'coordinator']);
    }

    public function view(User $user, Enrollment $enrollment): bool
    {
        if ($this->isStaff($user) || $user->hasRole('teacher')) {
            return true;
        }

        if ($user->hasRole('student')) {
            return $enrollment->student_id === $user->id;
        }

        if ($user->hasRole('guardian')) {
            return $user->students()->where('student_id', $enrollment->student_id)->exists();
        }

        return false;
    }

    public function viewByStudent(User $user, int $studentId): bool
    {
        if ($this->isStaff($user) || $user->hasRole('teacher')) {
            return true;
        }

        if ($user->hasRole('student')) {
            return $user->id === $studentId;
        }

        if ($user->hasRole('guardian')) {
            return $user->students()->where('student_id', $studentId)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function update(User $user, Enrollment $enrollment): bool
    {
        return $this->isStaff($user);
    }

    public function delete(User $user, Enrollment $enrollment): bool
    {
        return $this->isStaff($user);
    }

    public function transfer(User $user, Enrollment $enrollment): bool
    {
        return $this->isStaff($user);
    }
}
