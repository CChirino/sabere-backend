<?php

namespace App\Policies;

use App\Models\SubjectArea;
use App\Models\User;

class SubjectAreaPolicy
{
    private function isStaff(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'director', 'coordinator']);
    }

    public function view(User $user, SubjectArea $area): bool
    {
        return $user->hasAnyRole(['admin', 'director', 'coordinator', 'teacher', 'student', 'guardian']);
    }

    public function create(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function update(User $user, SubjectArea $area): bool
    {
        return $this->isStaff($user);
    }

    public function delete(User $user, SubjectArea $area): bool
    {
        return $this->isStaff($user);
    }
}
