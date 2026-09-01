<?php

namespace App\Policies;

use App\Models\Grade;
use App\Models\User;

class GradePolicy
{
    private function isStaff(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'director', 'coordinator']);
    }

    public function view(User $user, Grade $grade): bool
    {
        return $user->hasAnyRole(['admin', 'director', 'coordinator', 'teacher', 'student', 'guardian']);
    }

    public function create(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function update(User $user, Grade $grade): bool
    {
        return $this->isStaff($user);
    }

    public function delete(User $user, Grade $grade): bool
    {
        return $this->isStaff($user);
    }

    public function viewSubjects(User $user, Grade $grade): bool
    {
        return $this->view($user, $grade);
    }
}
