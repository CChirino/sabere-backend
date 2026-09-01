<?php

namespace App\Policies;

use App\Models\StudentEvaluationScore;
use App\Models\User;

class StudentEvaluationScorePolicy
{
    private function isStaff(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'director', 'coordinator']);
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, StudentEvaluationScore $score): bool
    {
        $assignment = $score->subjectAssignment;

        if ($this->isStaff($user)) {
            return true;
        }

        if ($user->hasRole('teacher')) {
            return $assignment->teacher_id === $user->id;
        }

        if ($user->id === $score->student_id) {
            return true;
        }

        if ($user->hasRole('guardian')) {
            return $user->students()->where('users.id', $score->student_id)->exists();
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

    public function update(User $user, StudentEvaluationScore $score): bool
    {
        if ($this->isStaff($user)) {
            return true;
        }

        $assignment = $score->subjectAssignment;

        return $user->hasRole('teacher') && $assignment->teacher_id === $user->id;
    }

    public function delete(User $user, StudentEvaluationScore $score): bool
    {
        return $this->update($user, $score);
    }
}
