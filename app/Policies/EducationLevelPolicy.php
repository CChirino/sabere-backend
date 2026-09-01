<?php

namespace App\Policies;

use App\Models\EducationLevel;
use App\Models\User;

class EducationLevelPolicy
{
    private function isStaff(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'director', 'coordinator']);
    }

    public function view(User $user, EducationLevel $level): bool
    {
        return $user->hasAnyRole(['admin', 'director', 'coordinator', 'teacher', 'student', 'guardian']);
    }

    public function create(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function update(User $user, EducationLevel $level): bool
    {
        return $this->isStaff($user);
    }

    public function delete(User $user, EducationLevel $level): bool
    {
        return $this->isStaff($user);
    }
}
