<?php

namespace App\Policies;

use App\Models\RecoveryRegistration;
use App\Models\User;

class RecoveryRegistrationPolicy
{
    private function isStaff(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'director', 'coordinator']);
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, RecoveryRegistration $registration): bool
    {
        if ($this->isStaff($user)) {
            return true;
        }

        if ($user->id === $registration->student_id) {
            return true;
        }

        if ($user->hasRole('guardian')) {
            return $user->students()->where('users.id', $registration->student_id)->exists();
        }

        return false;
    }

    public function grade(User $user): bool
    {
        return $this->isStaff($user) || $user->hasRole('teacher');
    }
}
