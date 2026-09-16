<?php

namespace App\Policies;

use App\Models\DisciplinaryRecord;
use App\Models\User;

class DisciplinaryRecordPolicy
{
    private function isStaff(User $user): bool
    {
        return $user->hasRole(['admin', 'director', 'coordinator']);
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'director', 'coordinator', 'teacher', 'student', 'guardian']);
    }

    public function view(User $user, DisciplinaryRecord $record): bool
    {
        if ($this->isStaff($user) || $record->recorded_by === $user->id) {
            return true;
        }

        if ($record->is_private) {
            return false;
        }

        if ($user->hasRole('student')) {
            return $user->id === $record->student_id;
        }

        if ($user->hasRole('guardian')) {
            return $user->students()->where('users.id', $record->student_id)->exists();
        }

        if ($user->hasRole('teacher')) {
            return $user->subjectAssignments()->where('section_id', $record->section_id)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'director', 'coordinator', 'teacher']);
    }

    public function update(User $user, DisciplinaryRecord $record): bool
    {
        return $this->isStaff($user) || $record->recorded_by === $user->id;
    }

    public function delete(User $user, DisciplinaryRecord $record): bool
    {
        return $this->isStaff($user) || $record->recorded_by === $user->id;
    }
}
