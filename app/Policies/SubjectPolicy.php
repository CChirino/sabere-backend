<?php

namespace App\Policies;

use App\Models\Subject;
use App\Models\User;

class SubjectPolicy
{
    private function isStaff(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'director', 'coordinator']);
    }

    public function view(User $user, Subject $subject): bool
    {
        return $user->hasAnyRole(['admin', 'director', 'coordinator', 'teacher', 'student', 'guardian']);
    }

    public function create(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function update(User $user, Subject $subject): bool
    {
        return $this->isStaff($user);
    }

    public function delete(User $user, Subject $subject): bool
    {
        return $this->isStaff($user);
    }

    public function manageGrades(User $user): bool
    {
        return $this->isStaff($user);
    }
}
