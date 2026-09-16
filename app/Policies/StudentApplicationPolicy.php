<?php

namespace App\Policies;

use App\Models\StudentApplication;
use App\Models\User;

class StudentApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'director', 'coordinator']);
    }

    public function view(User $user, StudentApplication $application): bool
    {
        return $this->isStaff($user);
    }

    public function create(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function update(User $user, StudentApplication $application): bool
    {
        return $this->isStaff($user) && $application->status === 'pending';
    }

    public function delete(User $user, StudentApplication $application): bool
    {
        return $this->isStaff($user);
    }

    public function approve(User $user, StudentApplication $application): bool
    {
        return $this->isStaff($user) && $application->status === 'pending';
    }

    public function reject(User $user, StudentApplication $application): bool
    {
        return $this->isStaff($user) && $application->status === 'pending';
    }

    private function isStaff(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'director', 'coordinator']);
    }
}
