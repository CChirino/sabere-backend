<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    private function isStaff(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'director', 'coordinator']);
    }

    public function view(User $user, Task $task): bool
    {
        if ($this->isStaff($user) || $user->hasRole('teacher')) {
            return true;
        }

        if ($user->hasRole('student')) {
            $enrollment = $user->activeEnrollment();

            return $enrollment && $enrollment->section_id === $task->subjectAssignment->section_id;
        }

        return false;
    }

    public function create(User $user, \App\Models\SubjectAssignment $assignment): bool
    {
        if ($this->isStaff($user)) {
            return true;
        }

        return $user->hasRole('teacher') && $assignment->teacher_id === $user->id;
    }

    public function update(User $user, Task $task): bool
    {
        if ($this->isStaff($user)) {
            return true;
        }

        if (! $user->hasRole('teacher')) {
            return false;
        }

        return $task->subjectAssignment->teacher_id === $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }

    public function togglePublish(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }

    public function viewForStudent(User $user, int $studentId): bool
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
}
