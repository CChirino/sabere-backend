<?php

namespace App\Policies;

use App\Models\TaskSubmission;
use App\Models\User;

class TaskSubmissionPolicy
{
    private function isStaff(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'director', 'coordinator']);
    }

    public function view(User $user, TaskSubmission $submission): bool
    {
        if ($this->isStaff($user)) {
            return true;
        }

        if ($user->hasRole('teacher')) {
            return $submission->task->subjectAssignment->teacher_id === $user->id;
        }

        if ($user->hasRole('student')) {
            return $submission->student_id === $user->id;
        }

        if ($user->hasRole('guardian')) {
            return $user->students()->where('student_id', $submission->student_id)->exists();
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

    public function create(User $user, \App\Models\Task $task): bool
    {
        // Solo estudiantes de la sección de la tarea pueden entregar
        if (! $user->hasRole('student')) {
            return false;
        }

        $enrollment = $user->activeEnrollment();

        return $enrollment && $enrollment->section_id === $task->subjectAssignment->section_id;
    }

    public function grade(User $user, TaskSubmission $submission): bool
    {
        if ($this->isStaff($user)) {
            return true;
        }

        if (! $user->hasRole('teacher')) {
            return false;
        }

        return $submission->task->subjectAssignment->teacher_id === $user->id;
    }

    public function returnForCorrection(User $user, TaskSubmission $submission): bool
    {
        return $this->grade($user, $submission);
    }
}
