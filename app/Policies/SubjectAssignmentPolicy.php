<?php

namespace App\Policies;

use App\Models\SubjectAssignment;
use App\Models\User;

class SubjectAssignmentPolicy
{
    private function isStaff(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'director', 'coordinator']);
    }

    public function view(User $user, SubjectAssignment $assignment): bool
    {
        if ($this->isStaff($user)) {
            return true;
        }

        if ($user->hasRole('teacher')) {
            return $assignment->teacher_id === $user->id;
        }

        if ($user->hasRole('student')) {
            $enrollment = $user->activeEnrollment();

            return $enrollment && $enrollment->section_id === $assignment->section_id;
        }

        if ($user->hasRole('guardian')) {
            $studentIds = $user->students()->pluck('users.id');

            return \App\Models\Enrollment::whereIn('student_id', $studentIds)
                ->where('section_id', $assignment->section_id)
                ->where('status', 'active')
                ->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function update(User $user, SubjectAssignment $assignment): bool
    {
        return $this->isStaff($user);
    }

    public function delete(User $user, SubjectAssignment $assignment): bool
    {
        return $this->isStaff($user);
    }

    public function viewByTeacher(User $user, int $teacherId): bool
    {
        if ($this->isStaff($user)) {
            return true;
        }

        return $user->hasRole('teacher') && $user->id === $teacherId;
    }

    public function viewStudents(User $user, SubjectAssignment $assignment): bool
    {
        return $this->view($user, $assignment);
    }
}
