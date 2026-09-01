<?php

namespace App\Policies;

use App\Models\AcademicPeriod;
use App\Models\User;

class AcademicPeriodPolicy
{
    private function isStaff(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'director', 'coordinator']);
    }

    public function view(User $user, AcademicPeriod $period): bool
    {
        return $user->hasAnyRole(['admin', 'director', 'coordinator', 'teacher', 'student', 'guardian']);
    }

    public function create(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function update(User $user, AcademicPeriod $period): bool
    {
        return $this->isStaff($user);
    }

    public function delete(User $user, AcademicPeriod $period): bool
    {
        return $this->isStaff($user);
    }
}
