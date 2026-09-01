<?php

namespace App\Policies;

use App\Models\StudentGuardian;
use App\Models\User;

class StudentGuardianPolicy
{
    private function isStaff(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'director', 'coordinator']);
    }

    public function view(User $user, StudentGuardian $relation): bool
    {
        if ($this->isStaff($user) || $user->hasRole('teacher')) {
            return true;
        }

        if ($user->hasRole('guardian')) {
            return $relation->guardian_id === $user->id;
        }

        if ($user->hasRole('student')) {
            return $relation->student_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function update(User $user, StudentGuardian $relation): bool
    {
        return $this->isStaff($user);
    }

    public function delete(User $user, StudentGuardian $relation): bool
    {
        return $this->isStaff($user);
    }

    public function viewStudentsByGuardian(User $user, int $guardianId): bool
    {
        if ($this->isStaff($user) || $user->hasRole('teacher')) {
            return true;
        }

        return $user->hasRole('guardian') && $user->id === $guardianId;
    }

    public function viewGuardiansByStudent(User $user, int $studentId): bool
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

    public function viewStudentInfo(User $user, int $studentId): bool
    {
        return $this->viewGuardiansByStudent($user, $studentId);
    }
}
